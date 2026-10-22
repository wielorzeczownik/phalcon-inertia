#!/usr/bin/env bash
set -euo pipefail

: "${PHALCON_VERSION:?set PHALCON_VERSION}"
: "${PSR_VERSION:?set PSR_VERSION}"
: "${PHPUNIT_VERSION:?set PHPUNIT_VERSION}"
: "${COMPOSER_VERSION:?set COMPOSER_VERSION}"

cd /tmp

case "$PHALCON_VERSION" in
4.*)
  curl -fsSL "https://github.com/jbboehr/php-psr/archive/refs/tags/v${PSR_VERSION}.tar.gz" | tar xz
  (cd "php-psr-${PSR_VERSION}" && phpize && ./configure && make -j"$(nproc)" && make install)
  docker-php-ext-enable psr
  ;;
esac

case "$PHALCON_VERSION" in
6.*)
  apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
  curl -fsSL -o composer.phar "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar"
  mkdir -p /opt/phalcon
  php composer.phar require --working-dir=/opt/phalcon --no-interaction --no-progress "phalcon/phalcon:${PHALCON_VERSION}"
  echo 'auto_prepend_file=/opt/phalcon/vendor/autoload.php' >"$PHP_INI_DIR/conf.d/phalcon.ini"
  ;;
5.*)
  pecl install "phalcon-${PHALCON_VERSION}"
  docker-php-ext-enable phalcon
  ;;
*)
  curl -fsSL "https://github.com/phalcon/cphalcon/archive/refs/tags/v${PHALCON_VERSION}.tar.gz" | tar xz
  (cd "cphalcon-${PHALCON_VERSION}/build" && ./install)
  docker-php-ext-enable phalcon
  ;;
esac

curl -fsSL -o /usr/local/bin/phpunit "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
chmod +x /usr/local/bin/phpunit

rm -rf /tmp/*
useradd --system --create-home tester
