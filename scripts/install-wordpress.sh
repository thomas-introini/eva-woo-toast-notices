#!/usr/bin/env bash
set -euo pipefail

# Backwards-compatible wrapper: just delegate to the main start script.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
"$SCRIPT_DIR/start.sh"

