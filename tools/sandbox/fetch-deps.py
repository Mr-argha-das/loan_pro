#!/usr/bin/env python3
"""Fetch Composer packages from GitHub (codeload) for offline installation.

Sandbox-only tooling. The Arena sandbox cannot reach packagist.org, so we pull
each package's source archive from GitHub and expose the extracted directories
to Composer as *path* repositories.
"""
from __future__ import annotations

import json
import os
import re
import ssl
import sys
import tarfile
import time
import urllib.error
import urllib.request
from pathlib import Path

DEPS = Path(os.environ.get("DEPS_DIR", "/opt/loanpro-setup/deps"))
TOKEN = os.environ.get("GH_TOKEN", "")
CTX = ssl.create_default_context()

# Packages whose GitHub repository is not simply <vendor>/<name>.
REPO_OVERRIDES = {
    "nesbot/carbon": "briannesbitt/Carbon",
    "psr/container": "php-fig/container",
    "psr/log": "php-fig/log",
    "psr/clock": "php-fig/clock",
    "psr/simple-cache": "php-fig/simple-cache",
    "psr/http-message": "php-fig/http-message",
    "psr/http-client": "php-fig/http-client",
    "psr/http-factory": "php-fig/http-factory",
    "psr/event-dispatcher": "php-fig/event-dispatcher",
    "psr/cache": "php-fig/cache",
    "symfony/polyfill-php80": "symfony/polyfill-php80",
    "symfony/polyfill-php82": "symfony/polyfill-php82",
    "symfony/polyfill-php83": "symfony/polyfill-php83",
    "symfony/polyfill-php84": "symfony/polyfill-php84",
    "symfony/polyfill-intl-idn": "symfony/polyfill-intl-idn",
    "symfony/polyfill-intl-normalizer": "symfony/polyfill-intl-normalizer",
    "monolog/monolog": "Seldaek/monolog",
    "league/flysystem": "thephpleague/flysystem",
    "league/flysystem-local": "thephpleague/flysystem-local",
    "league/flysystem-aws-s3-v3": "thephpleague/flysystem-aws-s3-v3",
    "league/uri": "thephpleague/uri",
    "league/uri-interfaces": "thephpleague/uri-interfaces",
    "league/commonmark": "thephpleague/commonmark",
    "league/config": "thephpleague/config",
    "league/mime-type-detection": "thephpleague/mime-type-detection",
    "masterminds/html5": "Masterminds/html5-php",
    "tijsverkoyen/css-to-inline-styles": "tijsverkoyen/CssToInlineStyles",
    "guzzlehttp/guzzle": "guzzle/guzzle",
    "guzzlehttp/psr7": "guzzle/psr7",
    "guzzlehttp/promises": "guzzle/promises",
    "guzzlehttp/uri-template": "guzzle/uri-template",
    "phpoption/phpoption": "schmittjoh/php-option",
    "graham-campbell/result-type": "GrahamCampbell/Result-Type",
    "egulias/email-validator": "egulias/EmailValidator",
    "nette/schema": "nette/schema",
    "dflydev/dot-access-data": "dflydev/dflydev-dot-access-data",
}

# Package name prefixes that are always satisfied by another package's
# "replace" section (Laravel ships illuminate/* inside laravel/framework).
PROVIDED_BY_PARENT = {
    "illuminate/": "laravel/framework",
}


def api(path: str):
    url = path if path.startswith("http") else f"https://api.github.com{path}"
    req = urllib.request.Request(url)
    req.add_header("User-Agent", "loanpro-sandbox-fetch")
    req.add_header("Accept", "application/vnd.github+json")
    if TOKEN:
        req.add_header("Authorization", f"Bearer {TOKEN}")
    with urllib.request.urlopen(req, timeout=60, context=CTX) as r:
        return json.loads(r.read())


def repo_for(name: str) -> str:
    return REPO_OVERRIDES.get(name, name)


def list_tags(repo: str):
    tags = []
    for page in range(1, 6):
        try:
            data = api(f"/repos/{repo}/tags?per_page=100&page={page}")
        except urllib.error.HTTPError as e:
            if e.code in (404, 403, 451):
                return None
            raise
        if not data:
            break
        tags += [t["name"] for t in data]
        if len(data) < 100:
            break
    return tags


SEMVER = re.compile(r"^v?(\d+)\.(\d+)(?:\.(\d+))?(?:[-+](.*))?$")


def parse_ver(tag: str):
    m = SEMVER.match(tag)
    if not m:
        return None
    major, minor, patch, extra = m.group(1), m.group(2), m.group(3) or "0", m.group(4) or ""
    stable = extra == ""
    return (int(major), int(minor), int(patch), 0 if stable else -1)


def satisfies(version, constraint: str) -> bool:
    if version is None:
        return False
    constraint = (constraint or "").strip()
    if constraint in ("", "*", "self.version"):
        return True
    for or_part in re.split(r"\|+", constraint):
        parts = [c.strip() for c in or_part.split(",") if c.strip()]
        if parts and all(satisfies_single(version, c) for c in parts):
            return True
    return False


def satisfies_single(version, c: str) -> bool:
    if c in ("", "*"):
        return True
    if c.startswith("^"):
        base = parse_ver(c[1:])
        if not base:
            return True
        parts = c[1:].split(".")
        if base[0] > 0:
            upper = (base[0] + 1, 0, 0)
        elif len(parts) > 1 and int(parts[1]) > 0:
            upper = (0, int(parts[1]) + 1, 0)
        else:
            upper = (0, 0, base[2] + 1)
        return base[:3] <= version[:3] < upper
    if c.startswith("~"):
        base = parse_ver(c[1:])
        if not base:
            return True
        if c[1:].count(".") >= 2:
            upper = (base[0], base[1] + 1, 0)
        else:
            upper = (base[0] + 1, 0, 0)
        return base[:3] <= version[:3] < upper
    m = re.match(r"^(>=|<=|>|<|==|=|!=)?\s*v?(\d+)(?:\.(\d+))?(?:\.(\d+))?", c)
    if not m:
        return True
    op = m.group(1) or "=="
    target = (int(m.group(2)), int(m.group(3) or 0), int(m.group(4) or 0))
    if op in ("==", "="):
        if "*" in c:
            width = len([p for p in c.split(".") if p.strip().rstrip("*").isdigit()])
            return version[:width] == target[:width]
        return version[:3] == target
    if op == ">=":
        return version[:3] >= target
    if op == "<=":
        return version[:3] <= target
    if op == ">":
        return version[:3] > target
    if op == "<":
        return version[:3] < target
    if op == "!=":
        return version[:3] != target
    return True


class Fetcher:
    def __init__(self):
        self.constraints: dict[str, list[str]] = {}
        self.chosen: dict[str, str] = {}
        self.failed: dict[str, str] = {}
        self.queue: list[tuple[str, str]] = []
        self.missing: set[str] = set()
        self.replaced: set[str] = set()

    def provided_by_parent(self, name: str) -> bool:
        for prefix, parent in PROVIDED_BY_PARENT.items():
            if name.startswith(prefix) and parent in self.chosen:
                return True
        return False

    def add_requirement(self, name: str, constraint: str, why: str = "root"):
        name = name.lower()
        if self.provided_by_parent(name):
            return
        lst = self.constraints.setdefault(name, [])
        if (constraint or "*") not in lst:
            lst.append(constraint or "*")
        self.queue.append((name, why))

    def pick(self, name: str):
        repo = repo_for(name)
        tags = list_tags(repo)
        if tags is None:
            self.failed[name] = f"repo {repo} not found"
            return None
        parsed = [(parse_ver(t), t) for t in tags]
        parsed = [(p, t) for p, t in parsed if p]
        if not parsed:
            self.failed[name] = "no semver tags"
            return None
        parsed.sort(key=lambda x: x[0], reverse=True)
        cons = self.constraints[name]
        stable = [pt for pt in parsed if pt[0][3] >= 0]
        for pool in (stable, parsed):
            for p, t in pool:
                if all(satisfies(p, c) for c in cons):
                    return (p, t)
        self.failed[name] = f"no tag satisfies {cons}"
        return None

    def fetch(self, name: str, tag: str) -> bool:
        repo = repo_for(name)
        dest = DEPS / name
        if (dest / "composer.json").exists():
            return True
        url = f"https://codeload.github.com/{repo}/tar.gz/refs/tags/{tag}"
        tmp = Path("/tmp/_dl.tgz")
        for attempt in range(3):
            try:
                req = urllib.request.Request(url, headers={"User-Agent": "loanpro-sandbox"})
                with urllib.request.urlopen(req, timeout=300, context=CTX) as r, open(tmp, "wb") as f:
                    while True:
                        chunk = r.read(1 << 16)
                        if not chunk:
                            break
                        f.write(chunk)
                break
            except Exception as e:  # noqa: BLE001
                if attempt == 2:
                    self.failed[name] = f"download error {e}"
                    return False
                time.sleep(2)
        dest.mkdir(parents=True, exist_ok=True)
        with tarfile.open(tmp) as tf:
            members = tf.getmembers()
            root = members[0].name.split("/")[0] + "/"
            for m in members:
                if not m.name.startswith(root) or m.name == root:
                    continue
                m.name = m.name[len(root):]
                try:
                    tf.extract(m, dest, filter="data")
                except TypeError:
                    tf.extract(m, dest)
        if not (dest / "composer.json").exists():
            self.failed[name] = "no composer.json in archive"
            return False
        return True

    def run(self):
        processed = set()
        while self.queue:
            name, why = self.queue.pop(0)
            if name in processed:
                continue
            processed.add(name)
            tag = self.chosen.get(name)
            if tag is None:
                best = self.pick(name)
                if best is None:
                    print(f"  !! cannot resolve {name} (wanted by {why}): {self.failed.get(name)}", flush=True)
                    self.missing.add(name)
                    continue
                self.chosen[name] = best[1]
                tag = best[1]
            if not self.fetch(name, tag):
                print(f"  !! fetch failed {name}@{tag}: {self.failed.get(name)}", flush=True)
                self.missing.add(name)
                continue
            cj = json.loads((DEPS / name / "composer.json").read_text())
            ver = re.sub(r"^v", "", tag)
            cj["version"] = ver
            (DEPS / name / "composer.json").write_text(json.dumps(cj, indent=4))
            for rep in (cj.get("replace") or {}):
                self.replaced.add(rep.lower())
            for rep in (cj.get("provide") or {}):
                self.replaced.add(rep.lower())
            print(f"  - {name}@{ver}", flush=True)
            for dep, depcons in (cj.get("require") or {}).items():
                if "/" not in dep or dep.lower() == "php":
                    continue
                if dep.startswith(("ext-", "lib-")):
                    continue
                self.add_requirement(dep, depcons, why=name)
        return self.missing


if __name__ == "__main__":
    import shutil

    f = Fetcher()
    if len(sys.argv) > 2 and sys.argv[1] == "--refetch":
        # e.g. --refetch "brick/math:^0.14" "symfony/console:^7.0"
        for spec in sys.argv[2:]:
            name, _, cons = spec.partition(":")
            name = name.lower()
            shutil.rmtree(DEPS / name, ignore_errors=True)
            f.add_requirement(name, cons or "*")
        missing = f.run()
        print("refetched:", len(f.chosen), "missing:", sorted(missing))
        raise SystemExit(0)

    roots = json.loads(sys.argv[1]) if len(sys.argv) > 1 else {}
    for n, c in roots.items():
        f.add_requirement(n, c)
    missing = f.run()
    print("packages:", len(f.chosen), "missing:", sorted(missing))
