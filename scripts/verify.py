#!/usr/bin/env python3
"""Verify compiled catalogs, release contents and SVG assets."""
import gettext
import hashlib
import importlib.util
import json
import pathlib
import xml.etree.ElementTree as ET
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
catalog = json.loads((ROOT / "languages/translations.json").read_text())
for locale in ("fa_IR", "ar", "ru_RU", "ur"):
    with (ROOT / ("languages/hamgoo-live-chat-" + locale + ".mo")).open("rb") as file:
        translation = gettext.GNUTranslations(file)
    for text, values in catalog.items():
        assert translation.gettext(text) == values[locale], (locale, text)
    print(locale + ": runtime catalog verified (" + str(len(catalog)) + ")")
for svg in (ROOT / "assets/images").glob("*.svg"):
    node = ET.parse(svg).getroot()
    assert node.tag.endswith("svg") and node.get("viewBox") == "0 0 480 320"
    assert "<script" not in svg.read_text() and "http://" not in svg.read_text().replace("http://www.w3.org/2000/svg", "")
spec = importlib.util.spec_from_file_location("package", ROOT / "scripts/package.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
archive_path = module.package()
digest = hashlib.sha256(archive_path.read_bytes()).hexdigest()
with zipfile.ZipFile(archive_path) as archive:
    names = archive.namelist()
    assert len(names) > 15
    assert not any("/vendor/" in name or "/tests/" in name or "/scripts/" in name or "/.github/" in name for name in names)
    assert not any(name.endswith("translations.json") for name in names)
    assert archive.read("hamgoo-live-chat/hamgoo-live-chat.php").startswith(b"<?php")
    print("Release ZIP: " + str(len(names)) + " runtime files; SHA256 " + digest)
