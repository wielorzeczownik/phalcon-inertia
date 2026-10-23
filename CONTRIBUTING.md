# Contributing to phalcon-inertia

Thank you for considering a contribution. This document covers everything you need to get started.

## Overview

An Inertia.js server adapter for Phalcon 3.4 to 5 on PHP 5.6 to 8.5. Library code has to parse and run on PHP 5.6, so it uses no scalar or return types, no `??`, no arrow functions and no trailing commas in calls. Tooling (Pint, PHPUnit 11) runs on PHP 8.2.

## Project structure

```text
.
├── src/
│   ├── Inertia.php      the service: render, share, errors, flash, location, 303 listener
│   ├── Page.php         pure page object builder: partial reloads, prop types, merge and deferred metadata
│   ├── Prop.php         always, optional, deferred, merge, once and scroll props
│   ├── Routes.php       named route export for JavaScript
│   ├── Exception.php    base of everything the library throws
│   ├── ValidationException.php  thrown by validate(), turned into a redirect back or a Precognition answer
│   ├── Csrf.php         XSRF-TOKEN cookie and check, as a dispatcher listener
│   ├── aliases.php      InertiaServiceProvider alias for the loaded Phalcon version
│   ├── helpers.php      global inertia() helper
│   ├── Provider/        service provider, one class per Phalcon interface signature
│   ├── Testing/         assertInertia() trait and AssertableInertia for PHPUnit
│   ├── SsrGateway.php   client for the Inertia SSR server
│   └── Vite.php         script and style tags from the Vite manifest or dev server
├── js/                 route() helper in TypeScript, built into the committed js/dist
├── tests/               PHPUnit tests, written to run on PHPUnit 5.7 through 11
├── e2e/                 Phalcon example app in Vue, React and Svelte with SSR and Playwright tests
├── docker/              one test image per PHP and Phalcon pair, and its test runner
└── scripts/
    ├── test-matrix.sh   builds and runs every test image
    ├── test-e2e.sh      builds the e2e app and runs Playwright against it
    └── bump-version.sh  decides the next release version from git-cliff output
```

## Development setup

```bash
git clone https://github.com/wielorzeczownik/phalcon-inertia.git
cd phalcon-inertia
composer install
```

Composer needs PHP 8.2 or newer. The tests need the phalcon extension, so they run in Docker.

## Running the example app

The [e2e app](e2e) doubles as a demo. With PHP and the Phalcon extension installed locally, build it and start the SSR server and PHP, then open <http://127.0.0.1:8000>:

```bash
npm --prefix e2e ci
npm --prefix e2e run build   # Vue, or CLIENT=react / CLIENT=svelte
node e2e/bootstrap/ssr/ssr.js &
SSR_URL=http://127.0.0.1:13714 php -S 127.0.0.1:8000 -t e2e/public e2e/public/index.php
```

Without the `node` line the pages render on the client only.

## Running checks locally

CI runs exactly these commands. Anything that passes here passes there.

```bash
# PHP formatting
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

# js/dist is committed: rebuild it after changing js/src, CI fails otherwise
npm --prefix js run build

# Shell scripts
shfmt --diff scripts/ docker/
shellcheck scripts/*.sh docker/*.sh

# Formatting
npx prettier --check .

# Workflows
actionlint

# Markdown
markdownlint-cli2 "**/*.md" '!CHANGELOG.md'
```

To apply autofixable findings instead of just checking: `composer format`, `npx prettier --write .`.

The first `composer test:matrix` compiles Phalcon 3.4 and 4.1 from source and takes several minutes. Later runs reuse the Docker images.

## Tests

Tests extend `PhalconInertia\Tests\TestCase` and must run on PHPUnit 5.7, 9.6 and 11, so:

- no `setUp()` or other overridden fixture methods, build state in private helpers
- no data providers and no annotations or attributes
- assertions that exist in all three versions, plus `assertHasText()` and `assertLacksText()` from the base class

## Commit style

This project uses [Conventional Commits](https://www.conventionalcommits.org/). Commit messages drive automatic changelog generation and version bumping.

| Prefix      | When to use                                |
| ----------- | ------------------------------------------ |
| `feat:`     | New feature or behavior                    |
| `fix:`      | Bug fix                                    |
| `perf:`     | Performance improvement                    |
| `refactor:` | Code change without behavior change        |
| `test:`     | Tests only                                 |
| `docs:`     | Documentation only                         |
| `style:`    | Formatting, no logic change                |
| `build:`    | Build tooling and development dependencies |
| `ci:`       | Workflows and CI configuration             |
| `chore:`    | Maintenance that fits nothing above        |

Scope names the area, not the file: `page`, `props`, `routes`, `csrf`, `ssr`, `js`, `docker`, `release`, `deps`.

Breaking changes must include `BREAKING CHANGE:` in the commit footer.

## Pull requests

- Keep PRs focused on a single concern.
- Reference any related issue in the PR description.
- All CI checks must pass before merging.

## Reporting bugs

Open an [issue](https://github.com/wielorzeczownik/phalcon-inertia/issues) and include:

- What you did
- What you expected
- What actually happened
- PHP, Phalcon and Inertia client versions

> For security issues, read [SECURITY.md](SECURITY.md) before opening a public issue.

## License

By contributing you agree that your changes will be licensed under the [MIT License](LICENSE).
