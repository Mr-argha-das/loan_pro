#!/usr/bin/env python3
"""Fetch the *exact* package versions recorded in ``composer.sandbox.lock``.

``fetch-deps.py`` resolves constraints against GitHub tags, which costs one or
more api.github.com calls per package (rate limited to 60 req/h for anonymous
access).  This script instead reads the locked versions and downloads the tag
archives straight from codeload.github.com - one request per package, no API
calls, and it reproduces the previously working vendor tree byte for byte.

Usage::

    DEPS_DIR=/opt/loanpro-setup/deps python3 tools/sandbox/fetch-locked-deps.py
"""
from __future__ import annotations

import json
import os
import sys
import tarfile
import urllib.error
import urllib.request
from pathlib import Path

import importlib.util

_common_path = Path(__file__).resolve().parent / "fetch-deps.py"
_spec = importlib.util.spec_from_file_location("fetch_deps_common", _common_path)
_common = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(_common)  # type: ignore[union-attr]

CTX = _common.CTX
repo_for = _common.repo_for

ROOT = Path(__file__).resolve().parents[2]
DEPS = Path(os.environ.get("DEPS_DIR", "/opt/loanpro-setup/deps"))
LOCK = ROOT / "composer.sandbox.lock"


def candidate_tags(version: str) -> list[str]:
    return [f"v{version}", version]


def download(repo: str, tag: str, dest: Path) -> bool:
    url = f"https://codeload.github.com/{repo}/tar.gz/refs/tags/{tag}"
    tmp = Path("/tmp/_dl_locked.tgz")

    try:
        req = urllib.request.Request(url, headers={"User-Agent": "loanpro-sandbox"})
        with urllib.request.urlopen(req, timeout=180, context=CTX) as response, open(tmp, "wb") as handle:
            while True:
                chunk = response.read(1 << 16)
                if not chunk:
                    break
                handle.write(chunk)
    except urllib.error.HTTPError as error:
        if error.code == 404:
            return False
        raise

    dest.mkdir(parents=True, exist_ok=True)
    with tarfile.open(tmp) as archive:
        members = archive.getmembers()
        root = members[0].name.split("/")[0] + "/"
        for member in members:
            if not member.name.startswith(root) or member.name == root:
                continue
            member.name = member.name[len(root):]
            try:
                archive.extract(member, dest, filter="data")
            except TypeError:  # Python < 3.12
                archive.extract(member, dest)

    return (dest / "composer.json").exists()


def main() -> int:
    lock = json.loads(LOCK.read_text())
    packages = (lock.get("packages") or []) + (lock.get("packages-dev") or [])

    DEPS.mkdir(parents=True, exist_ok=True)

    ok, failed = 0, []

    for package in packages:
        name = package["name"]
        version = package["version"].split("+")[0]
        dest = DEPS / name

        if (dest / "composer.json").exists():
            ok += 1
            continue

        repo = repo_for(name)
        fetched = False

        for tag in candidate_tags(version):
            if download(repo, tag, dest):
                fetched = True
                break

        if not fetched:
            failed.append(f"{name}@{version} ({repo})")
            print(f"  !! {name}@{version}", flush=True)
            continue

        manifest = json.loads((dest / "composer.json").read_text())
        manifest["version"] = version
        (dest / "composer.json").write_text(json.dumps(manifest, indent=4))
        ok += 1
        print(f"  - {name}@{version}", flush=True)

    print(f"packages: {ok} fetched, {len(failed)} failed")
    if failed:
        print("failed:", *failed, sep="\n  ")

    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
