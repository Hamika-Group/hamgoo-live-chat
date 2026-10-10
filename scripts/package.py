#!/usr/bin/env python3
"""Build a deterministic installable ZIP from an explicit runtime allowlist."""
import argparse
import pathlib
import re
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
SLUG = "hamgoo-live-chat"
FILES = ("hamgoo-live-chat.php", "uninstall.php", "readme.txt", "LICENSE")
DIRS = ("includes", "assets")


def package(tag=None):
    main = (ROOT / "hamgoo-live-chat.php").read_text()
    readme = (ROOT / "readme.txt").read_text()
    version = re.search(r"^\s*\* Version:\s*(\S+)", main, re.M).group(1)
    constant = re.search(r"define\(\s*'HAMGOO_VERSION',\s*'([^']+)'", main).group(1)
    stable = re.search(r"^Stable tag:\s*(\S+)", readme, re.M).group(1)
    if not re.fullmatch(r"\d+\.\d+\.\d+", version):
        raise SystemExit("Version must be stable X.Y.Z.")
    if version != constant or version != stable or (tag and tag != "v" + version):
        raise SystemExit("Plugin version, constant, stable tag and release tag must match.")
    files = [ROOT / name for name in FILES]
    for name in DIRS:
        files.extend(p for p in (ROOT / name).rglob("*") if p.is_file())
    files.extend(p for p in (ROOT / "languages").iterdir() if p.suffix in (".po", ".mo", ".pot"))
    for locale in ("fa_IR", "ar", "ru_RU", "ur"):
        if not (ROOT / ("languages/hamgoo-live-chat-" + locale + ".mo")).exists():
            raise SystemExit("Missing runtime translation: " + locale)
    target = ROOT / "dist"
    target.mkdir(exist_ok=True)
    output = target / (SLUG + "-" + version + ".zip")
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for file in sorted(files):
            relative = str(file.relative_to(ROOT))
            if any(part.startswith(".") for part in file.relative_to(ROOT).parts):
                raise SystemExit("Unexpected hidden runtime file: " + relative)
            entry = zipfile.ZipInfo(SLUG + "/" + relative, date_time=(2026, 1, 1, 0, 0, 0))
            entry.compress_type = zipfile.ZIP_DEFLATED
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, file.read_bytes())
    with zipfile.ZipFile(output) as archive:
        if archive.testzip() is not None:
            raise SystemExit("Invalid ZIP.")
        assert all(name.startswith(SLUG + "/") for name in archive.namelist())
    print(output)
    return output


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--tag")
    package(parser.parse_args().tag)
