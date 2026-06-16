#!/usr/bin/env python3
"""Create Jules sessions for WP EU Cookie Suite (one PR per prompt)."""

from __future__ import annotations

import json
import os
import re
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PROMPTS_DIR = ROOT / "prompts"
API = "https://jules.googleapis.com/v1alpha/sessions"
SOURCE = os.environ.get(
    "JULES_SOURCE", "sources/github/EvgeniSasim/wp-eu-cookie-suite"
)
BRANCH = os.environ.get("JULES_BRANCH", "main")

CC_ORDER = [
    "jules-task-cc-01-scaffold.md",
    "jules-task-cc-02-admin-shell.md",
    "jules-task-cc-03-cookieconsent-frontend.md",
    "jules-task-cc-04-categories-options.md",
    "jules-task-cc-05-script-blocker.md",
    "jules-task-cc-06-script-registry.md",
    "jules-task-cc-07-wp-consent-api.md",
    "jules-task-cc-08-google-consent-mode.md",
    "jules-task-cc-09-cookie-scanner.md",
    "jules-task-cc-10-cookie-inventory.md",
    "jules-task-cc-11-multilingual.md",
    "jules-task-cc-12-banner-customizer.md",
    "jules-task-cc-13-legal-shortcodes.md",
    "jules-task-cc-14-integrations.md",
    "jules-task-cc-15-tests.md",
]

CC_BRANCHES = {
    "jules-task-cc-01-scaffold.md": "jules/cc-01-scaffold",
    "jules-task-cc-02-admin-shell.md": "jules/cc-02-admin",
    "jules-task-cc-03-cookieconsent-frontend.md": "jules/cc-03-banner",
    "jules-task-cc-04-categories-options.md": "jules/cc-04-categories",
    "jules-task-cc-05-script-blocker.md": "jules/cc-05-blocker",
    "jules-task-cc-06-script-registry.md": "jules/cc-06-registry",
    "jules-task-cc-07-wp-consent-api.md": "jules/cc-07-wp-consent",
    "jules-task-cc-08-google-consent-mode.md": "jules/cc-08-gcm",
    "jules-task-cc-09-cookie-scanner.md": "jules/cc-09-scanner",
    "jules-task-cc-10-cookie-inventory.md": "jules/cc-10-inventory",
    "jules-task-cc-11-multilingual.md": "jules/cc-11-i18n",
    "jules-task-cc-12-banner-customizer.md": "jules/cc-12-customizer",
    "jules-task-cc-13-legal-shortcodes.md": "jules/cc-13-legal",
    "jules-task-cc-14-integrations.md": "jules/cc-14-integrations",
    "jules-task-cc-15-tests.md": "jules/cc-15-tests",
}


def extract_prompt(md_path: Path) -> str:
    text = md_path.read_text(encoding="utf-8")
    m = re.search(r"```markdown\s*\n(.*?)```", text, re.DOTALL)
    if not m:
        raise ValueError(f"No ```markdown block in {md_path}")
    return m.group(1).strip()


def create_session(*, title: str, prompt: str, branch_hint: str) -> dict:
    key = os.environ.get("JULES_API_KEY")
    if not key:
        raise SystemExit("JULES_API_KEY not set")
    body = {
        "title": title,
        "prompt": f"{prompt}\n\nTarget git branch for PR: `{branch_hint}`.",
        "sourceContext": {
            "source": SOURCE,
            "githubRepoContext": {"startingBranch": BRANCH},
        },
        "automationMode": "AUTO_CREATE_PR",
    }
    req = urllib.request.Request(
        API,
        data=json.dumps(body).encode("utf-8"),
        headers={
            "Content-Type": "application/json",
            "x-goog-api-key": key,
        },
        method="POST",
    )
    with urllib.request.urlopen(req, timeout=120) as resp:
        return json.loads(resp.read().decode())


def resolve_tasks() -> list[Path]:
    single = os.environ.get("JULES_TASK", "").strip()
    if single:
        path = PROMPTS_DIR / single if not single.startswith("prompts/") else ROOT / single
        if not path.is_file():
            raise SystemExit(f"JULES_TASK not found: {path}")
        return [path]
    if os.environ.get("JULES_CC_ALL") == "1":
        return [PROMPTS_DIR / n for n in CC_ORDER if (PROMPTS_DIR / n).is_file()]
    raise SystemExit("Set JULES_TASK=jules-task-cc-NN-….md or JULES_CC_ALL=1")


def session_title(path: Path) -> str:
    name = path.stem.replace("jules-task-cc-", "cc-")
    return f"wp-eu-cookie-suite: {name[:60]}"


def main() -> None:
    tasks = resolve_tasks()
    for path in tasks:
        branch = CC_BRANCHES.get(path.name, f"jules/{path.stem}")
        prompt = extract_prompt(path)
        title = session_title(path)
        print(f"Creating session: {path.name} → {branch} …", flush=True)
        try:
            out = create_session(title=title, prompt=prompt, branch_hint=branch)
        except urllib.error.HTTPError as e:
            body = e.read().decode() if e.fp else ""
            print(f"HTTP {e.code}: {body}", file=sys.stderr)
            raise SystemExit(1) from e
        sid = out.get("name", out.get("id", out))
        print(f"  OK: {sid}", flush=True)


if __name__ == "__main__":
    main()
