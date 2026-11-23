# Contributing

Every contribution helps, including a typo fix, a bug report, or a question about how something works. For anything bigger than a small fix, opening an issue first is a good idea, so we can agree on the approach before you spend time on it.

One thing to know up front: library code in `src/` has to parse and run on PHP 5.6, so it uses no scalar or return types, no `??`, no arrow functions and no trailing commas in calls. The tooling (Pint, PHPUnit 11) runs on PHP 8.2.

## Setup

```bash
git clone https://github.com/wielorzeczownik/phalcon-inertia.git
cd phalcon-inertia
composer install
```

Composer needs PHP 8.2 or newer. The tests need the phalcon extension, so they run in Docker.

## Example app

The [e2e app](e2e) doubles as a demo. With PHP and the Phalcon extension installed locally, build it, start the SSR server and PHP, then open <http://127.0.0.1:8000>:

```bash
npm --prefix e2e ci
npm --prefix e2e run build   # Vue, or CLIENT=react / CLIENT=svelte
node e2e/bootstrap/ssr/ssr.js &
SSR_URL=http://127.0.0.1:13714 php -S 127.0.0.1:8000 -t e2e/public e2e/public/index.php
```

Without the `node` line the pages render on the client only.

## Checks

CI runs these on every pull request. You don't have to run them all locally, since CI will point out anything that's off:

```bash
composer validate --no-check-publish
composer lint

# PHPUnit for every PHP and Phalcon pair, or only the pairs given
composer test:matrix
composer test:matrix -- php56-phalcon34

# Playwright against the e2e app, oldest and newest pair by default, every client
composer test:e2e
CLIENTS=react composer test:e2e
composer test:e2e -- php73-phalcon34

# route() helper
npm --prefix js ci
composer test:js
npm --prefix js run lint
npm --prefix js run typecheck
npm --prefix js run build    # js/dist is committed, rebuild it after changing js/src

shfmt --diff scripts/ docker/
shellcheck scripts/*.sh docker/*.sh
npx prettier --check .
actionlint
markdownlint-cli2 "**/*.md" '!CHANGELOG.md'
```

`composer format` and `npx prettier --write .` fix what they can on their own.

The first `composer test:matrix` compiles Phalcon 3.4 and 4.1 from source and takes several minutes. Later runs reuse the Docker images.

## Tests

Tests extend `PhalconInertia\Tests\TestCase` and have to run on PHPUnit 5.7, 9.6 and 11, so:

- no `setUp()` or other overridden fixture methods, build state in private helpers
- no data providers, annotations or attributes
- only assertions that exist in all three versions, plus `assertHasText()` and `assertLacksText()` from the base class

## Commits and pull requests

Commits follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat(props): ...`, `fix(ssr): ...`), because the changelog and version bumps are generated from them. The scope names the area, e.g. `page`, `props`, `routes`, `csrf`, `ssr`, `js`, `docker`. Don't worry if you get it wrong, it can be fixed before merging.

Smaller PRs that do one thing are easier to review. Tests for behavior changes are very welcome. If you're not sure how to test something across all the PHP versions, open the PR anyway and we'll work it out together.

Bugs and ideas go to [issues](https://github.com/wielorzeczownik/phalcon-inertia/issues). Security problems go through [SECURITY.md](SECURITY.md).

Contributions are licensed under the [MIT License](LICENSE).
