#!/usr/bin/env bash
set -euo pipefail

root=$(cd "$(dirname "$0")/.." && pwd)
compose=(docker compose -f "$root/e2e/compose.yml")

if [[ $# -gt 0 ]]; then
  variants=("$@")
else
  variants=(php56-phalcon34 php85-phalcon6)
fi

export VARIANT="${variants[0]}"

cleanup() {
  "${compose[@]}" down --volumes --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

"${compose[@]}" run --rm node sh -c 'npm ci --no-audit --no-fund && npm run build'

for variant in "${variants[@]}"; do
  echo "==> e2e $variant"
  if [[ -z "${SKIP_IMAGE_BUILD:-}" ]]; then
    "$root/scripts/test-matrix.sh" "$variant"
  fi
  export VARIANT="$variant"
  "${compose[@]}" up --detach --wait php ssr
  "${compose[@]}" run --rm node npx playwright test
  "${compose[@]}" rm --stop --force php >/dev/null
done
