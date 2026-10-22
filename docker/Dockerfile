ARG PHP_IMAGE
FROM ${PHP_IMAGE}

ARG PHALCON_VERSION
ARG PSR_VERSION=1.2.0
ARG COMPOSER_VERSION=2.10.3
ARG PHPUNIT_VERSION

COPY install.sh /tmp/install.sh
RUN /tmp/install.sh

COPY run-tests.sh /usr/local/bin/run-tests
USER tester
WORKDIR /app
CMD ["run-tests"]
