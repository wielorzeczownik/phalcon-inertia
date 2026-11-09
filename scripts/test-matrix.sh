#!/usr/bin/env bash
set -euo pipefail

variant_args() {
  case "$1" in
  php56-phalcon34)
    echo "PHP_IMAGE=php:5.6-cli@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
    # renovate: datasource=github-releases depName=phalcon/cphalcon extractVersion=^v(?<version>.+)$
    echo "PHALCON_VERSION=3.4.5"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=5.7.27"
    ;;
  php73-phalcon34)
    echo "PHP_IMAGE=php:7.3-cli@sha256:bd6f2dc7a4e4537e927355c94a74c3022911aa504278f5f0fd94016c90dfaecc"
    # renovate: datasource=github-releases depName=phalcon/cphalcon extractVersion=^v(?<version>.+)$
    echo "PHALCON_VERSION=3.4.5"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=9.6.38"
    ;;
  php74-phalcon41)
    echo "PHP_IMAGE=php:7.4-cli@sha256:620a6b9f4d4feef2210026172570465e9d0c1de79766418d3affd09190a7fda5"
    # renovate: datasource=github-releases depName=phalcon/cphalcon extractVersion=^v(?<version>.+)$
    echo "PHALCON_VERSION=4.1.3"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=9.6.38"
    ;;
  php84-phalcon5)
    echo "PHP_IMAGE=php:8.4-cli@sha256:d63e8b80cd4c6cfd01cfa7c910522e299cdc7ca1a2b6d6563db48df3c17e6c55"
    # renovate: datasource=github-releases depName=phalcon/cphalcon extractVersion=^v(?<version>.+)$
    echo "PHALCON_VERSION=5.23.0"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=11.5.57"
    ;;
  php85-phalcon5)
    echo "PHP_IMAGE=php:8.5-cli@sha256:50956f094e90a716d43bea28087d447555ad80510e386ab8c9c10d8c70d781a9"
    # renovate: datasource=github-releases depName=phalcon/cphalcon extractVersion=^v(?<version>.+)$
    echo "PHALCON_VERSION=5.23.0"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=11.5.57"
    ;;
  php85-phalcon6)
    echo "PHP_IMAGE=php:8.5-cli@sha256:50956f094e90a716d43bea28087d447555ad80510e386ab8c9c10d8c70d781a9"
    # renovate: datasource=packagist depName=phalcon/phalcon
    echo "PHALCON_VERSION=6.0.0RC4"
    # renovate: datasource=packagist depName=phpunit/phpunit
    echo "PHPUNIT_VERSION=11.5.57"
    ;;
  *) return 1 ;;
  esac
}

root=$(cd "$(dirname "$0")/.." && pwd)

if [[ $# -gt 0 ]]; then
  variants=("$@")
else
  variants=(php56-phalcon34 php73-phalcon34 php74-phalcon41 php84-phalcon5 php85-phalcon5 php85-phalcon6)
fi

for variant in "${variants[@]}"; do
  if ! spec=$(variant_args "$variant"); then
    echo "Unknown variant: $variant" >&2
    exit 1
  fi

  read -d '' -ra pairs <<<"$spec" || true
  build_args=()
  for pair in "${pairs[@]}"; do
    build_args+=(--build-arg "$pair")
  done

  echo "==> $variant"
  docker build -q -t "phalcon-inertia:$variant" "${build_args[@]}" "$root/docker" >/dev/null
  docker run --rm -v "$root:/app:ro" "phalcon-inertia:$variant"
done
