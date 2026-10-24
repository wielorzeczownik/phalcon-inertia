ARG PHP_IMAGE
FROM ${PHP_IMAGE}

ARG PHALCON_VERSION
# renovate: datasource=github-tags depName=jbboehr/php-psr extractVersion=^v(?<version>.+)$
ARG PSR_VERSION=1.2.0
# renovate: datasource=github-releases depName=composer/composer
ARG COMPOSER_VERSION=2.10.3
ARG PHPUNIT_VERSION
# TODO: phalcon/volt has no tagged release yet. Once 1.0.0 is out, switch to
# "datasource=packagist depName=phalcon/volt" with VOLT_VERSION, update install.sh
# and drop the git-refs custom manager from renovate.json.
# renovate: datasource=git-refs depName=https://github.com/phalcon/volt branch=1.0.x
ARG VOLT_COMMIT=23585c18c5226c33e33c469f180aad3f780413c5

COPY install.sh /tmp/install.sh
RUN /tmp/install.sh

COPY run-tests.sh /usr/local/bin/run-tests
USER tester
WORKDIR /app
CMD ["run-tests"]
