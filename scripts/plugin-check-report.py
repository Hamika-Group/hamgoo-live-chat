#!/usr/bin/env python3
"""Fail CI on any finding in Plugin Check's per-file JSON output."""
import json
import pathlib
import re
import sys


def findings(report):
    decoder = json.JSONDecoder()
    results = []
    for match in re.finditer(r"(?m)^FILE: (.+)\r?\n", report):
        messages, _ = decoder.raw_decode(report[match.end():].lstrip())
        if not isinstance(messages, list):
            raise ValueError("Expected a JSON array for " + match.group(1))
        for message in messages:
            if not isinstance(message, dict) or message.get("type") not in ("ERROR", "WARNING"):
                raise ValueError("Unrecognized Plugin Check finding")
            results.append((match.group(1), message))
    if report.strip() and not results and "[" in report and not re.search(r"(?m)^FILE: ", report):
        raise ValueError("Unexpected Plugin Check report format")
    return results


def main():
    results = findings(pathlib.Path(sys.argv[1]).read_text(encoding="utf-8"))
    for file, message in results:
        description = (message["code"] + ": " + message["message"])
        description = description.replace("%", "%25").replace("\r", "%0D").replace("\n", "%0A")
        print("::error::" + file + ": " + description)
    if results:
        raise SystemExit("Plugin Check reported " + str(len(results)) + " finding(s).")
    print("Official Plugin Check: no remaining errors or warnings.")


if __name__ == "__main__":
    main()
