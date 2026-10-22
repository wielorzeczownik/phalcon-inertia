#!/usr/bin/env bash
set -euo pipefail

echo '<?php echo "PHP ", PHP_VERSION, ", Phalcon ", phpversion("phalcon") ?: Composer\InstalledVersions::getPrettyVersion("phalcon/phalcon"), PHP_EOL;' | php

case "$(phpunit --version)" in
*"PHPUnit 5."*) exec phpunit --no-configuration --bootstrap tests/bootstrap.php tests ;;
*"PHPUnit 9."*) exec phpunit --no-configuration --bootstrap tests/bootstrap.php --do-not-cache-result --fail-on-warning tests ;;
*) exec phpunit --do-not-cache-result ;;
esac
