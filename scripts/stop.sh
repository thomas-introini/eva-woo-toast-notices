#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "🛑 Stopping EVA Toast Notices development environment..."
docker compose down
echo "✅ Containers stopped."

