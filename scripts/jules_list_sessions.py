#!/usr/bin/env python3
"""List recent Jules sessions (filter wp-eu-cookie-suite)."""

from __future__ import annotations

import json
import os
import sys
import urllib.request

API = "https://jules.googleapis.com/v1alpha/sessions"
FILTER = os.environ.get("JULES_FILTER", "wp-eu-cookie-suite")


def main() -> None:
    key = os.environ.get("JULES_API_KEY")
    if not key:
        raise SystemExit("JULES_API_KEY not set")
    url = f"{API}?pageSize=20"
    req = urllib.request.Request(url, headers={"x-goog-api-key": key})
    with urllib.request.urlopen(req, timeout=60) as resp:
        data = json.loads(resp.read().decode())
    for s in data.get("sessions", []):
        title = s.get("title", "")
        if FILTER and FILTER not in title:
            continue
        name = s.get("name", "")
        state = s.get("state", s.get("status", "?"))
        print(f"{state:12} {name}  {title}")


if __name__ == "__main__":
    main()
