#!/usr/bin/env bash
set -euo pipefail

: "${PHALCON_VERSION:?set PHALCON_VERSION}"
: "${PSR_VERSION:?set PSR_VERSION}"
: "${PHPUNIT_VERSION:?set PHPUNIT_VERSION}"

cd /tmp

case "$PHALCON_VERSION" in
4.*)
  curl -fsSL "https://github.com/jbboehr/php-psr/archive/refs/tags/v${PSR_VERSION}.tar.gz" | tar xz
  (cd "php-psr-${PSR_VERSION}" && phpize && ./configure && make -j"$(nproc)" && make install)
  docker-php-ext-enable psr
  ;;
esac

case "$PHALCON_VERSION" in
5.*)
  pecl install "phalcon-${PHALCON_VERSION}"
  ;;
*)
  curl -fsSL "https://github.com/phalcon/cphalcon/archive/refs/tags/v${PHALCON_VERSION}.tar.gz" | tar xz
  (cd "cphalcon-${PHALCON_VERSION}/build" && ./install)
  ;;
esac

docker-php-ext-enable phalcon

curl -fsSL -o /usr/local/bin/phpunit "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
chmod +x /usr/local/bin/phpunit

rm -rf /tmp/*
useradd --system --create-home tester
