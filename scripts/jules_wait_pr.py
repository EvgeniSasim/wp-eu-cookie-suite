#!/usr/bin/env python3
"""Poll Jules session until done; print PR link if created."""

from __future__ import annotations

import json
import os
import subprocess
import sys
import time
import urllib.request

API = "https://jules.googleapis.com/v1alpha/sessions"
REPO = "EvgeniSasim/wp-eu-cookie-suite"


def session_state(session_id: str) -> str:
    key = os.environ["JULES_API_KEY"]
    url = f"{API}/{session_id}"
    req = urllib.request.Request(url, headers={"x-goog-api-key": key})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return json.loads(resp.read().decode()).get("state", "?")


def open_prs() -> list[dict]:
    out = subprocess.check_output(
        ["gh", "pr", "list", "--repo", REPO, "--state", "open", "--json", "number,title,url,headRefName"],
        text=True,
    )
    return json.loads(out)


def main() -> None:
    if len(sys.argv) < 2:
        raise SystemExit(f"Usage: {sys.argv[0]} SESSION_ID [max_wait_seconds]")
    sid = sys.argv[1].replace("sessions/", "")
    max_wait = int(sys.argv[2]) if len(sys.argv) > 2 else 900
    deadline = time.time() + max_wait
    while time.time() < deadline:
        state = session_state(sid)
        prs = open_prs()
        print(f"state={state} open_prs={len(prs)}", flush=True)
        if prs:
            print(json.dumps(prs, indent=2))
            return
        if state in ("COMPLETED", "FAILED", "CANCELLED"):
            return
        time.sleep(30)
    print("timeout", file=sys.stderr)


if __name__ == "__main__":
    main()
