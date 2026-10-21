#!/usr/bin/env bash
set -euo pipefail

variant_args() {
  case "$1" in
  php56-phalcon34) echo "PHP_IMAGE=php:5.6-cli@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143 PHALCON_VERSION=3.4.5 PHPUNIT_VERSION=5.7.27" ;;
  php73-phalcon34) echo "PHP_IMAGE=php:7.3-cli@sha256:bd6f2dc7a4e4537e927355c94a74c3022911aa504278f5f0fd94016c90dfaecc PHALCON_VERSION=3.4.5 PHPUNIT_VERSION=9.6.38" ;;
  php74-phalcon41) echo "PHP_IMAGE=php:7.4-cli@sha256:620a6b9f4d4feef2210026172570465e9d0c1de79766418d3affd09190a7fda5 PHALCON_VERSION=4.1.2 PHPUNIT_VERSION=9.6.38" ;;
  php84-phalcon5) echo "PHP_IMAGE=php:8.4-cli@sha256:e2432ac85c3454004d89e3d75615abd2d205b7ffb9590e586fad1a053f016bb3 PHALCON_VERSION=5.23.0 PHPUNIT_VERSION=11.5.57" ;;
  php85-phalcon5) echo "PHP_IMAGE=php:8.5-cli@sha256:8e5d746f87655d89573e76d2b9125ed1b1578e47d3c466a2b2fdfc35593d9b41 PHALCON_VERSION=5.23.0 PHPUNIT_VERSION=11.5.57" ;;
  *) return 1 ;;
  esac
}

root=$(cd "$(dirname "$0")/.." && pwd)

if [[ $# -gt 0 ]]; then
  variants=("$@")
else
  variants=(php56-phalcon34 php73-phalcon34 php74-phalcon41 php84-phalcon5 php85-phalcon5)
fi

for variant in "${variants[@]}"; do
  if ! spec=$(variant_args "$variant"); then
    echo "Unknown variant: $variant" >&2
    exit 1
  fi

  read -ra pairs <<<"$spec"
  build_args=()
  for pair in "${pairs[@]}"; do
    build_args+=(--build-arg "$pair")
  done

  echo "==> $variant"
  docker build -q -t "phalcon-inertia:$variant" "${build_args[@]}" "$root/docker" >/dev/null
  docker run --rm -v "$root:/app:ro" "phalcon-inertia:$variant"
done
