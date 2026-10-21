#!/usr/bin/env bash
set -euo pipefail

emit_outputs() {
  {
    echo "released=$1"
    echo "version=$2"
    echo "tag=$3"
  } >>"$GITHUB_OUTPUT"
}

current_tag=$(git describe --tags --abbrev=0 --match 'v[0-9]*' 2>/dev/null || true)
next_tag=$(echo "${CLIFF_BUMPED_VERSION:-}" | tr -d '[:space:]')

if [[ -z "$next_tag" ]]; then
  echo "Unable to determine next version from git-cliff"
  exit 1
fi

if [[ "$next_tag" != v* ]]; then
  next_tag="v$next_tag"
fi

next_version="${next_tag#v}"

if [[ -z "$current_tag" ]]; then
  echo "No release tag yet. Creating the initial release ${next_tag}."
  emit_outputs true "$next_version" "$next_tag"
  exit 0
fi

current_version="${current_tag#v}"

if [[ "$next_version" == "$current_version" ]]; then
  echo "No user-facing commits since ${current_tag}. Nothing to release."
  emit_outputs false "$current_version" "$current_tag"
  exit 0
fi

if [[ "$(printf '%s\n' "$current_version" "$next_version" | sort -V | tail -1)" != "$next_version" ]]; then
  echo "git-cliff returned $next_version which is lower than current $current_version. Nothing to release."
  emit_outputs false "$current_version" "$current_tag"
  exit 0
fi

emit_outputs true "$next_version" "$next_tag"
