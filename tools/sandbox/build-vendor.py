#!/usr/bin/env python3
"""Assemble ``vendor/`` + a Composer-compatible autoloader without Composer.

This sandbox cannot reach Packagist or getcomposer.org, so `composer install`
is impossible.  Everything Composer would *produce* is however reproducible:

* the package sources come from ``tools/sandbox/fetch-locked-deps.py``
  (GitHub tag archives resolved from ``composer.sandbox.lock``),
* this script links them into ``vendor/`` and generates the exact autoload
  metadata Composer generates: ``autoload_*.php``, ``autoload_static.php``,
  ``autoload_real.php``, ``installed.json``, ``installed.php``,
  ``platform_check.php`` and the ``vendor/bin`` proxies.

Reusing Composer's own ``ClassLoader.php`` / ``InstalledVersions.php`` keeps the
runtime behaviour identical to a real ``composer install``.

Usage::

    DEPS_DIR=/home/user/loanpro-deps/deps python3 tools/sandbox/build-vendor.py
"""
from __future__ import annotations

import json
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPS = Path(os.environ.get("DEPS_DIR", "/home/user/loanpro-deps/deps"))
COMPOSER_SRC = Path(os.environ.get("COMPOSER_SRC", "/home/user/loanpro-deps/composer-src"))
VENDOR = ROOT / "vendor"
LOCK = ROOT / "composer.sandbox.lock"

CLASS_RE = re.compile(
    r"^[ \t]*(?:final[ \t]+|abstract[ \t]+|readonly[ \t]+)*(class|interface|trait|enum)[ \t]+(\w+)",
    re.MULTILINE,
)
NAMESPACE_RE = re.compile(r"^[ \t]*namespace[ \t]+([^;{\s]+)", re.MULTILINE)


def php_str(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def php_array(items: list[str], indent: int = 4) -> str:
    pad = " " * indent
    return "array(\n" + "".join(f"{pad}{item},\n" for item in items) + " " * (indent - 4) + ")"


def hardlink_tree(source: Path, target: Path) -> None:
    """Create an independent directory tree that shares inodes with `source`."""
    result = subprocess.run(["cp", "-al", str(source), str(target)], capture_output=True)
    if result.returncode != 0:
        shutil.copytree(source, target)


def link_packages(packages: list[dict]) -> None:
    linked = 0
    for package in packages:
        name = package["name"]
        source, target = DEPS / name, VENDOR / name
        if target.exists():
            shutil.rmtree(target)
        target.parent.mkdir(parents=True, exist_ok=True)
        hardlink_tree(source, target)
        linked += 1
    print(f"  linked {linked} packages into {VENDOR}")


def topo_sort(packages: list[dict]) -> list[dict]:
    """Order packages so that dependencies come before their dependents."""
    by_name = {p["name"]: p for p in packages}
    ordered: list[dict] = []
    seen: set[str] = set()

    def visit(name: str) -> None:
        if name in seen or name not in by_name:
            return
        seen.add(name)
        for dependency in by_name[name].get("require", {}):
            if dependency in by_name:
                visit(dependency)
        ordered.append(by_name[name])

    for package in packages:
        visit(package["name"])
    return ordered


def scan_classmap(directories: list[Path]) -> dict[str, str]:
    """Map fully qualified class names to files (Composer's classmap autoload)."""
    found: dict[str, str] = {}
    for directory in directories:
        files = [directory] if directory.is_file() else sorted(directory.rglob("*.php"))
        for path in files:
            if path.suffix != ".php":
                continue
            try:
                source = path.read_text(encoding="utf-8", errors="ignore")
            except OSError:
                continue
            namespaces = NAMESPACE_RE.findall(source)
            namespace = namespaces[0] if namespaces else ""
            for _, class_name in CLASS_RE.findall(source):
                fqcn = f"{namespace}\\{class_name}" if namespace else class_name
                found.setdefault(fqcn, str(path))
    return found


class AutoloadMap:
    def __init__(self) -> None:
        self.psr4: dict[str, list[str]] = {}
        self.psr0: dict[str, list[str]] = {}
        self.classmap: dict[str, str] = {}
        self.files: dict[str, str] = {}

    def merge_psr4(self, prefix: str, directories: list[str]) -> None:
        if not prefix.endswith("\\"):
            prefix += "\\"
        self.psr4.setdefault(prefix, []).extend(directories)

    def merge_psr0(self, prefix: str, directories: list[str]) -> None:
        self.psr0.setdefault(prefix, []).extend(directories)

    def merge_files(self, identifier: str, path: str) -> None:
        self.files.setdefault(identifier, path)


def collect(package: dict, base: Path, map_: AutoloadMap, dev: bool = False) -> None:
    manifest = package
    autoload = manifest.get("autoload") or {}
    if dev:
        # Merge autoload-dev on top of autoload *per section* (a plain dict merge
        # would drop the application's own psr-4 prefixes).
        autoload = {
            key: dict(value) for key, value in autoload.items() if isinstance(value, dict)
        } | {key: value for key, value in autoload.items() if not isinstance(value, dict)}
        for key, value in (manifest.get("autoload-dev") or {}).items():
            if isinstance(value, dict) and isinstance(autoload.get(key), dict):
                autoload[key] = {**autoload[key], **value}
            else:
                autoload[key] = value

    for prefix, paths in (autoload.get("psr-4") or {}).items():
        paths = paths if isinstance(paths, list) else [paths]
        # An empty path means "the package root" (common in symfony/*).
        map_.merge_psr4(prefix, [str((base / (p or ".")).resolve()) for p in paths])

    for prefix, paths in (autoload.get("psr-0") or {}).items():
        paths = paths if isinstance(paths, list) else [paths]
        map_.merge_psr0(prefix, [str((base / (p or ".")).resolve()) for p in paths])

    classmap_paths = autoload.get("classmap") or []
    classmap_paths = [classmap_paths] if isinstance(classmap_paths, str) else classmap_paths
    if classmap_paths:
        map_.classmap.update(scan_classmap([(base / p).resolve() for p in classmap_paths]))

    files = autoload.get("files") or []
    files = [files] if isinstance(files, str) else files
    for path in files:
        target = str((base / path).resolve())
        map_.merge_files(f"{package['name']}:{path}", target)


def write_autoload_files(map_: AutoloadMap) -> None:
    composer = VENDOR / "composer"
    composer.mkdir(parents=True, exist_ok=True)

    psr4_lines = [
        f"{php_str(prefix)} => {php_array([php_str(p) for p in dirs])}"
        for prefix, dirs in sorted(map_.psr4.items())
    ]
    (composer / "autoload_psr4.php").write_text(
        "<?php\n\n// autoload_psr4.php @generated by Composer\n\n"
        "$vendorDir = dirname(__DIR__);\n$baseDir = dirname($vendorDir);\n\n"
        "return array(\n" + "".join(f"    {line},\n" for line in psr4_lines) + ");\n"
    )

    psr0_lines = [
        f"{php_str(prefix)} => {php_array([php_str(p) for p in dirs])}"
        for prefix, dirs in sorted(map_.psr0.items())
    ]
    (composer / "autoload_namespaces.php").write_text(
        "<?php\n\n// autoload_namespaces.php @generated by Composer\n\n"
        "$vendorDir = dirname(__DIR__);\n$baseDir = dirname($vendorDir);\n\n"
        "return array(\n" + "".join(f"    {line},\n" for line in psr0_lines) + ");\n"
    )

    classmap_lines = [f"{php_str(k)} => {php_str(v)}" for k, v in sorted(map_.classmap.items())]
    (composer / "autoload_classmap.php").write_text(
        "<?php\n\n// autoload_classmap.php @generated by Composer\n\n"
        "$vendorDir = dirname(__DIR__);\n$baseDir = dirname($vendorDir);\n\n"
        "return array(\n" + "".join(f"    {line},\n" for line in classmap_lines) + ");\n"
    )

    files_lines = [f"{php_str(k)} => {php_str(v)}" for k, v in sorted(map_.files.items())]
    (composer / "autoload_files.php").write_text(
        "<?php\n\n// autoload_files.php @generated by Composer\n\n"
        "$vendorDir = dirname(__DIR__);\n$baseDir = dirname($vendorDir);\n\n"
        "return array(\n" + "".join(f"    {line},\n" for line in files_lines) + ");\n"
    )

    return None


def write_static(map_: AutoloadMap, suffix: str) -> None:
    composer = VENDOR / "composer"

    def expr(path: str) -> str:
        """PHP expression for a directory, as Composer writes it.

        Package paths become ``__DIR__ . '/../<vendor>/<package>/...'`` while
        application paths (``app/``, ``database/seeders/``) become
        ``$baseDir . '/app'``.
        """
        resolved = Path(path)
        try:
            return "__DIR__ . " + php_str("/../" + str(resolved.relative_to(VENDOR)))
        except ValueError:
            try:
                # vendor/composer -> project root is two levels up
                return "__DIR__ . " + php_str("/../../" + str(resolved.relative_to(VENDOR.parent)))
            except ValueError:
                return php_str(path)

    prefix_lengths: dict[str, list[str]] = {}
    prefix_dirs: list[str] = []
    for prefix, dirs in sorted(map_.psr4.items()):
        prefix_lengths.setdefault(prefix[0], []).append(
            f"{php_str(prefix)} => {len(prefix)}"
        )
        dir_entries = ", ".join(f"{i} => {expr(p)}" for i, p in enumerate(dirs))
        prefix_dirs.append(f"{php_str(prefix)} => array({dir_entries})")

    lengths = [
        f"{php_str(letter)} => array(\n" + "".join(f"            {line},\n" for line in lines) + "        )"
        for letter, lines in sorted(prefix_lengths.items())
    ]

    files_entries = [
        f"{php_str(k)} => {expr(v)}" for k, v in sorted(map_.files.items())
    ]
    classmap_entries = [
        f"{php_str(k)} => {expr(v)}" for k, v in sorted(map_.classmap.items())
    ]
    psr0_entries = [
        f"{php_str(prefix)} => array({', '.join(f'{i} => {expr(p)}' for i, p in enumerate(dirs))})"
        for prefix, dirs in sorted(map_.psr0.items())
    ]

    body = [
        "<?php",
        "",
        "// autoload_static.php @generated by Composer",
        "",
        "namespace Composer\\Autoload;",
        "",
        f"class ComposerStaticInit{suffix}",
        "{",
        "    public static $files = array (",
        "".join(f"        {line},\n" for line in files_entries) + "    );",
        "",
        "    public static $prefixLengthsPsr4 = array (",
        "".join(f"        {line},\n" for line in lengths) + "    );",
        "",
        "    public static $prefixDirsPsr4 = array (",
        "".join(f"        {line},\n" for line in prefix_dirs) + "    );",
        "",
        "    public static $prefixesPsr0 = array (",
        "".join(f"        {line},\n" for line in psr0_entries) + "    );",
        "",
        "    public static $classMap = array (",
        "".join(f"        {line},\n" for line in classmap_entries) + "    );",
        "",
        "    public static function getInitializer(ClassLoader $loader)",
        "    {",
        "        return \\Closure::bind(function () use ($loader) {",
        "            $loader->prefixLengthsPsr4 = ComposerStaticInit" + suffix + "::$prefixLengthsPsr4;",
        "            $loader->prefixDirsPsr4 = ComposerStaticInit" + suffix + "::$prefixDirsPsr4;",
        "            $loader->prefixesPsr0 = ComposerStaticInit" + suffix + "::$prefixesPsr0;",
        "            $loader->classMap = ComposerStaticInit" + suffix + "::$classMap;",
        "",
        "        }, null, ClassLoader::class);",
        "    }",
        "}",
        "",
    ]
    (composer / "autoload_static.php").write_text("\n".join(body))


def write_real(suffix: str) -> None:
    composer = VENDOR / "composer"
    (composer / "autoload_real.php").write_text(
        f"""<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit{suffix}
{{
    private static $loader;

    public static function loadClassLoader($class)
    {{
        if ('Composer\\Autoload\\ClassLoader' === $class) {{
            require __DIR__ . '/ClassLoader.php';
        }}
    }}

    /**
     * @return \\Composer\\Autoload\\ClassLoader
     */
    public static function getLoader()
    {{
        if (null !== self::$loader) {{
            return self::$loader;
        }}

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit{suffix}', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \\Composer\\Autoload\\ClassLoader(\\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit{suffix}', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\\Composer\\Autoload\\ComposerStaticInit{suffix}::getInitializer($loader));

        $loader->register(true);

        $filesToLoad = \\Composer\\Autoload\\ComposerStaticInit{suffix}::$files;
        $requireFile = \\Closure::bind(static function ($fileIdentifier, $file) {{
            if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {{
                $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;

                require $file;
            }}
        }}, null, null);
        foreach ($filesToLoad as $fileIdentifier => $file) {{
            $requireFile($fileIdentifier, $file);
        }}

        return $loader;
    }}
}}
"""
    )
    (VENDOR / "autoload.php").write_text(
        f"""<?php

// autoload.php @generated by Composer

require_once __DIR__ . '/composer/autoload_real.php';

return ComposerAutoloaderInit{suffix}::getLoader();
"""
    )


def write_platform_check(packages: list[dict]) -> None:
    requirements: dict[str, str] = {}
    for package in packages:
        for name, constraint in (package.get("require") or {}).items():
            requirements.setdefault(name, constraint)

    checks: list[str] = []
    if "php" in requirements:
        constraint = requirements["php"]
        match = re.search(r"(\d+)\.(\d+)", constraint)
        if match:
            required = int(match.group(1)) * 10000 + int(match.group(2)) * 100
            checks.append(
                f"if (!(PHP_VERSION_ID >= {required})) {{\n"
                f"    $issues[] = 'Your Composer dependencies require a PHP version \">= {match.group(1)}.{match.group(2)}.0\".';\n"
                "}"
            )
    for name, constraint in sorted(requirements.items()):
        if name.startswith("ext-"):
            extension = name[4:]
            checks.append(
                f"if (!extension_loaded('{extension}')) {{\n"
                f"    $issues[] = 'Your Composer dependencies require the PHP extension \"{extension}\".';\n"
                "}"
            )

    body = "\n".join(checks)
    (VENDOR / "composer" / "platform_check.php").write_text(
        f"""<?php

// platform_check.php @generated by Composer

$issues = array();

{body}

if ($issues) {{
    if (!headers_sent()) {{
        header('HTTP/1.1 500 Internal Server Error');
    }}
    if (!ini_get('display_errors')) {{
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {{
            fwrite(STDERR, 'Composer detected issues in your platform:' . PHP_EOL . PHP_EOL . implode(PHP_EOL, $issues) . PHP_EOL . PHP_EOL);
        }} elseif (!headers_sent()) {{
            echo 'Composer detected issues in your platform:' . PHP_EOL . PHP_EOL . str_replace('You are running ', '', implode(PHP_EOL, $issues)) . PHP_EOL . PHP_EOL;
        }}
    }}
    trigger_error('Composer detected issues in your platform: ' . implode(' ', $issues), E_USER_ERROR);
}}
"""
    )


def write_installed(packages: list[dict], root_manifest: dict) -> None:
    composer = VENDOR / "composer"
    records = []
    versions = {
        "root": {
            "name": root_manifest.get("name", "root"),
            "pretty_version": "dev-main",
            "version": "dev-main",
            "reference": None,
            "type": root_manifest.get("type", "project"),
            "install_path": __import__("os").path.relpath(ROOT, composer),
            "aliases": [],
            "dev": False,
        }
    }

    for package in packages:
        manifest = json.loads((VENDOR / package["name"] / "composer.json").read_text())
        version = manifest.get("version", package.get("version", "dev-main"))
        record = {**manifest, "install-path": f"../{package['name']}"}
        records.append(record)
        versions[package["name"]] = {
            "pretty_version": version,
            "version": version,
            "reference": None,
            "type": manifest.get("type", "library"),
            "install_path": f"../{package['name']}",
            "aliases": [],
            "dev_requirement": False,
        }

    (composer / "installed.json").write_text(
        json.dumps({"packages": records, "dev": False, "dev-package-names": []}, indent=4)
    )

    entries = []
    for name, info in sorted(versions.items()):
        entries.append(
            f"        {php_str(name)} => array(\n"
            f"            'pretty_version' => {php_str(info['pretty_version'])},\n"
            f"            'version' => {php_str(info['version'])},\n"
            f"            'reference' => NULL,\n"
            f"            'type' => {php_str(info['type'])},\n"
            f"            'install_path' => {php_str(info['install_path'])},\n"
            f"            'aliases' => array(),\n"
            f"            'dev_requirement' => false,\n"
            "        ),\n"
        )
    root = versions["root"]
    (composer / "installed.php").write_text(
        "<?php return array(\n"
        "    'root' => array(\n"
        f"        'name' => {php_str(root['name'])},\n"
        "        'pretty_version' => 'dev-main',\n"
        "        'version' => 'dev-main',\n"
        "        'reference' => NULL,\n"
        f"        'type' => {php_str(root['type'])},\n"
        f"        'install_path' => __DIR__ . {php_str('/' + root['install_path'] if not root['install_path'].startswith('/') else root['install_path'])},\n"
        "        'aliases' => array(),\n"
        "        'dev' => false,\n"
        "    ),\n"
        "    'versions' => array(\n"
        + "".join(entries)
        + "    ),\n"
        ");\n"
    )


def write_bin_proxies(packages: list[dict]) -> None:
    bindir = VENDOR / "bin"
    bindir.mkdir(exist_ok=True)
    count = 0
    for package in packages:
        manifest = json.loads((VENDOR / package["name"] / "composer.json").read_text())
        bins = manifest.get("bin") or []
        bins = [bins] if isinstance(bins, str) else bins
        for relative in bins:
            source = VENDOR / package["name"] / relative
            if not source.exists():
                continue
            proxy = bindir / Path(relative).name
            proxy.write_text(
                "#!/bin/sh\n"
                "# Autoload-aware proxy to the package binary.\n"
                'exec php "$(dirname "$0")/../'
                f'{package["name"]}/{relative}" "$@"\n'
            )
            proxy.chmod(0o755)
            count += 1
    print(f"  wrote {count} bin proxies")


def copy_composer_runtime() -> None:
    composer = VENDOR / "composer"
    for source, target in [
        (COMPOSER_SRC / "src/Composer/Autoload/ClassLoader.php", composer / "ClassLoader.php"),
        (COMPOSER_SRC / "src/Composer/InstalledVersions.php", composer / "InstalledVersions.php"),
    ]:
        shutil.copyfile(source, target)


def main() -> int:
    lock = json.loads(LOCK.read_text())
    packages = lock.get("packages") or []
    root_manifest = json.loads((ROOT / "composer.json").read_text())

    missing = [p["name"] for p in packages if not (DEPS / p["name"] / "composer.json").exists()]
    if missing:
        print("missing sources (run tools/sandbox/fetch-locked-deps.py):")
        print("  " + "\n  ".join(missing))
        return 2

    print("assembling vendor/")
    link_packages(packages)

    map_ = AutoloadMap()
    for package in topo_sort(packages):
        collect(package, VENDOR / package["name"], map_)
    collect(root_manifest, ROOT, map_, dev=True)

    suffix = lock.get("content-hash", "loanpro")[:32]

    write_autoload_files(map_)
    write_static(map_, suffix)
    write_real(suffix)
    write_platform_check(packages)
    copy_composer_runtime()
    write_installed(topo_sort(packages), root_manifest)
    write_bin_proxies(packages)

    print(
        f"  psr-4 prefixes: {len(map_.psr4)} | classmap: {len(map_.classmap)} | files: {len(map_.files)}"
    )
    print("vendor/ ready")
    return 0


if __name__ == "__main__":
    sys.exit(main())
