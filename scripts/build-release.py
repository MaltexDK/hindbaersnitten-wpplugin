#!/usr/bin/env python3
"""Validate release metadata and build a clean, installable WordPress ZIP."""
import argparse
from pathlib import Path
import re
import zipfile

ROOT = Path(__file__).resolve().parents[1]
SLUG = 'hindbaersnitten-wpplugin'
HEADER = 'hindbaersnitte-reviews.php'
ENTRIES = ['hindbaersnitte-reviews.php', 'includes', 'assets', 'lib', 'README.md']

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--tag', help='Require a stable vMAJOR.MINOR.PATCH tag matching the header')
parser.add_argument('--output-dir', type=Path, default=ROOT / 'dist')
args = parser.parse_args()
text = (ROOT / HEADER).read_text()
match = re.search(r'^\s*(?:\*\s*)?Version:\s*(\S+)', text, re.M)
if not match or not re.fullmatch(r'(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)', match[1]):
    parser.error('Header must contain a stable MAJOR.MINOR.PATCH version')
version = match[1]
if args.tag is not None and args.tag != 'v' + version:
    parser.error('Tag must be v' + version + ' to match the header')
if HEADER.endswith('.php'):
    constant = re.search(r"define\('HSNITTE_PLUGIN_VER',\s*'([^']+)'\)", text)
    if not constant or constant[1] != version:
        parser.error('HSNITTE_PLUGIN_VER must match the plugin header')
if not (ROOT / 'lib/plugin-update-checker/plugin-update-checker.php').is_file():
    parser.error('Bundled Plugin Update Checker is missing')
if not (ROOT / 'lib/plugin-update-checker/license.txt').is_file():
    parser.error('Bundled Plugin Update Checker license is missing')

args.output_dir.mkdir(parents=True, exist_ok=True)
archive = args.output_dir / (SLUG + '.zip')
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as package:
    for entry in ENTRIES:
        source = ROOT / entry
        if not source.exists():
            parser.error('Required package entry missing: ' + entry)
        paths = sorted(source.rglob('*')) if source.is_dir() else [source]
        for path in paths:
            relative = path.relative_to(ROOT)
            if not path.is_file() or any(p.startswith('.') for p in relative.parts):
                continue
            if path.name.lower() == 'desktop.ini' or path.suffix == '.bak' or path.name.endswith(' old.php'):
                continue
            package.write(path, str(Path(SLUG) / relative))
print(str(archive))
