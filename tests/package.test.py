#!/usr/bin/env python3
"""Exercise release metadata failures and portable checksums in disposable copies."""
import hashlib
import pathlib
import re
import shutil
import subprocess
import tempfile
import unittest

ROOT = pathlib.Path(__file__).resolve().parents[1]


class ReleasePackageTests(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.root = pathlib.Path(self.workspace.name)
        for name in ("scripts", "includes", "assets", "languages"):
            shutil.copytree(ROOT / name, self.root / name)
        for name in ("hamgoo-live-chat.php", "uninstall.php", "readme.txt", "LICENSE"):
            shutil.copy2(ROOT / name, self.root / name)
        self.version = re.search(r"^Stable tag:\s*(\S+)", (self.root / "readme.txt").read_text(), re.M).group(1)
        self.wrong_version = "9.9.9" if self.version != "9.9.9" else "0.0.0"

    def build(self, tag=None):
        tag = tag or "v" + self.version
        return subprocess.run(
            ["python3", str(self.root / "scripts/package.py"), "--tag", tag, "--checksum"],
            capture_output=True, text=True, check=False,
        )

    def test_checksum_is_portable_and_package_is_reproducible(self):
        self.assertEqual(self.build().returncode, 0)
        archive = self.root / ("dist/hamgoo-live-chat-" + self.version + ".zip")
        checksum = self.root / "dist/SHA256SUMS"
        first = archive.read_bytes()
        self.assertEqual(checksum.read_text(), hashlib.sha256(first).hexdigest() + "  " + archive.name + "\n")
        self.assertEqual(self.build().returncode, 0)
        self.assertEqual(archive.read_bytes(), first)

    def test_mismatched_tag_creates_no_artifact(self):
        self.assertNotEqual(self.build("v" + self.wrong_version).returncode, 0)
        self.assertFalse((self.root / "dist").exists())

    def test_mismatched_listing_version_creates_no_artifact(self):
        readme = self.root / "readme.txt"
        readme.write_text(readme.read_text().replace("Stable tag: " + self.version, "Stable tag: " + self.wrong_version))
        self.assertNotEqual(self.build().returncode, 0)
        self.assertFalse((self.root / "dist").exists())


if __name__ == "__main__":
    unittest.main()
