#!/usr/bin/env python3
"""Create an offline Composer setup for the sandbox.

`composer.json` in the repository targets Packagist (as a normal Laravel app
does).  This sandbox has no access to Packagist, so this script:

1. builds ``build/pkgs/<vendor>__<name>`` hard-link trees from the archives
   fetched by ``fetch-deps.py``, and
2. writes ``composer.sandbox.json`` - a copy of ``composer.json`` whose only
   repository is that local directory.

Run the install with::

    COMPOSER=composer.sandbox.json php /opt/php8/composer install
"""
from __future__ import annotations

import json
import os
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPS = Path(os.environ.get("DEPS_DIR", "/opt/loanpro-setup/deps"))
# Keep the linked package tree out of the repository working tree by default so
# the checkout stays small; override with PKGS_DIR when needed.
PKGS = Path(os.environ.get("PKGS_DIR", "/opt/loanpro-setup/pkgs"))


def main() -> int:
    if PKGS.exists():
        shutil.rmtree(PKGS)
    PKGS.mkdir(parents=True, exist_ok=True)
    count = 0
    for vendor in sorted(DEPS.iterdir()):
        if not vendor.is_dir():
            continue
        for name in sorted(vendor.iterdir()):
            if not (name / "composer.json").exists():
                continue
            target = PKGS / f"{vendor.name}__{name.name}"
            subprocess.run(["cp", "-al", str(name), str(target)], check=True)
            count += 1
    print(f"linked {count} packages into {PKGS}")

    composer = json.loads((ROOT / "composer.json").read_text())
    composer["repositories"] = [
        {"type": "path", "url": PKGS.as_posix() + "/*", "options": {"symlink": False}},
        {"packagist.org": False},
    ]
    composer["config"]["allow-plugins"] = {}
    out = ROOT / "composer.sandbox.json"
    out.write_text(json.dumps(composer, indent=4) + "\n")
    print(f"wrote {out}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
