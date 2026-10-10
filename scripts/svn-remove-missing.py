#!/usr/bin/env python3
"""Mark only missing paths inside the checked-out SVN directory for removal."""
import pathlib
import subprocess
import sys
import xml.etree.ElementTree as ET

workspace = pathlib.Path("svn").resolve()
root = ET.fromstring(sys.stdin.read())
for entry in root.findall(".//entry"):
    status = entry.find("wc-status")
    if status is not None and status.get("item") == "missing":
        path = pathlib.Path(entry.get("path")).resolve()
        if not path.is_relative_to(workspace):
            raise SystemExit("Refusing a path outside SVN checkout.")
        subprocess.run(["svn", "delete", "--force", str(path)], check=True)
