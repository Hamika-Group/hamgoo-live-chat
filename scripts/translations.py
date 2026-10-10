#!/usr/bin/env python3
"""Build auditable PO/POT and runtime MO files; fail on incomplete translations."""
import json
import pathlib
import re
import struct
import sys

ROOT = pathlib.Path(__file__).resolve().parents[1]
VERSION = re.search(r"^ \* Version: (\d+\.\d+\.\d+)$", (ROOT / "hamgoo-live-chat.php").read_text(encoding="utf-8"), re.M).group(1)
PATTERN = re.compile(
    r"(?:__|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\(\s*'((?:\\.|[^'\\])*)'\s*,\s*'hamgoo-live-chat'"
)


def messages():
    result = {}
    for path in sorted(ROOT.rglob("*.php")):
        if "tests" in path.parts or "vendor" in path.parts:
            continue
        source = path.read_text(encoding="utf-8")
        for match in PATTERN.finditer(source):
            text = match.group(1).replace("\\'", "'").replace("\\\\", "\\")
            reference = str(path.relative_to(ROOT)) + ":" + str(source[:match.start()].count("\n") + 1)
            result.setdefault(text, []).append(reference)
    return result


def quote(value):
    return json.dumps(value, ensure_ascii=False)


def header(locale):
    plurals = {
        "fa_IR": "nplurals=2; plural=(n > 1);",
        "ar": "nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5);",
        "ru_RU": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);",
        "ur": "nplurals=2; plural=(n != 1);",
    }
    return (
        f"Project-Id-Version: Hamgoo Live Chat {VERSION}\n"
        "Report-Msgid-Bugs-To: https://hamkaran.cloud/\n"
        "MIME-Version: 1.0\n"
        "Content-Type: text/plain; charset=UTF-8\n"
        "Content-Transfer-Encoding: 8bit\n"
        "Language: " + locale + "\n"
        "Plural-Forms: " + plurals.get(locale, "nplurals=2; plural=(n != 1);") + "\n"
        "X-Domain: hamgoo-live-chat\n"
    )


def write_mo(path, catalog):
    keys = sorted(catalog)
    originals = [key.encode("utf-8") for key in keys]
    translated = [catalog[key].encode("utf-8") for key in keys]
    count = len(keys)
    start = 28 + count * 16
    ids = b"\0".join(originals) + b"\0"
    values = b"\0".join(translated) + b"\0"
    first, second = [], []
    offset = start
    for value in originals:
        first.append((len(value), offset))
        offset += len(value) + 1
    offset = start + len(ids)
    for value in translated:
        second.append((len(value), offset))
        offset += len(value) + 1
    data = struct.pack("<7I", 0x950412DE, 0, count, 28, 28 + 8 * count, 0, 0)
    data += b"".join(struct.pack("<2I", *entry) for entry in first + second) + ids + values
    path.write_bytes(data)


def build():
    source = messages()
    data = json.loads((ROOT / "languages/translations.json").read_text(encoding="utf-8"))
    missing = set(source) - set(data)
    extra = set(data) - set(source)
    if missing or extra:
        raise SystemExit("Translation source mismatch.\nMissing: " + repr(sorted(missing)) + "\nExtra: " + repr(sorted(extra)))
    for locale in ("fa_IR", "ar", "ru_RU", "ur"):
        catalog = {"": header(locale)}
        lines = ["# Hamgoo Live Chat. GPL-2.0-or-later.", 'msgid ""', "msgstr " + quote(catalog[""]), ""]
        for message, references in source.items():
            value = data[message].get(locale)
            if not isinstance(value, str) or not value.strip():
                raise SystemExit("Missing " + locale + ": " + message)
            if re.findall(r"%[0-9$]*[sd]", message) != re.findall(r"%[0-9$]*[sd]", value):
                raise SystemExit("Placeholder mismatch: " + message)
            catalog[message] = value
            lines.extend(["#: " + " ".join(references), "msgid " + quote(message), "msgstr " + quote(value), ""])
        prefix = ROOT / ("languages/hamgoo-live-chat-" + locale)
        prefix.with_suffix(".po").write_text("\n".join(lines), encoding="utf-8")
        write_mo(prefix.with_suffix(".mo"), catalog)
        print(locale + ": " + str(len(source)) + " translated strings")
    pot = ["# Hamgoo Live Chat. GPL-2.0-or-later.", 'msgid ""', "msgstr " + quote(header("")), ""]
    for message, references in source.items():
        pot.extend(["#: " + " ".join(references), "msgid " + quote(message), 'msgstr ""', ""])
    (ROOT / "languages/hamgoo-live-chat.pot").write_text("\n".join(pot), encoding="utf-8")


if __name__ == "__main__":
    if "--list" in sys.argv:
        for index, text in enumerate(messages()):
            print(str(index) + " " + text)
    else:
        build()
