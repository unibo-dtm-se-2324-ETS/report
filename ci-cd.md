---
title: CI/CD
has_children: false
nav_order: 9
---

# CI/CD

## What is automated?

In this project, the CI/CD pipeline is designed to make sure that every code change is automatically checked and packaged. It is defined in the artifact repository and consists of three GitHub Actions workflows.

**Code checkout** - Every time code is pushed to the main branch, or a pull request is opened against it, the repository is automatically checked out using GitHub Actions.

**Syntax checking** - Every PHP file outside the `vendor` folder is automatically passed through `php -l`. If any file contains a syntax error, the run fails, preventing broken code from reaching a running site.

**Dependency validation** - The Composer workflow validates `composer.json` and `composer.lock`, restores a cached `vendor` folder, and installs the dependencies, including PHPUnit. This ensures that the declared dependencies can always be resolved.

**Artifact generation** - When application files change on the main branch, the project is copied into a clean release folder, zipped into `expense-tracker.zip`, and uploaded as a build artifact that can be downloaded and deployed.

Each workflow only starts when the files it cares about change, so a documentation-only commit does not run the PHP checks. All of them can also be started manually from the Actions tab.

## Why is this automated?

Automation is useful for several reasons:

- **Consistency**: the checks are executed the same way every time, instead of depending on me remembering to run them.
- **Speed**: changes are checked right after the push.
- **Immediate feedback**: a broken PHP file or an invalid dependency file is reported while the change is still fresh, so it can be fixed early.
- **Efficient packaging**: the deployable package is produced by the pipeline and not assembled by hand, which removes a manual step where mistakes are easy to make and hard to notice.

## GitHub Actions implementation

GitHub Actions is used as the automation tool. The workflows are YAML files located in `.github/workflows/`.

### PHP syntax check (`main.yml`)

This workflow triggers on pushes and pull requests to the main branch that change a PHP file.

- **Checkout code**: the `actions/checkout` action retrieves the repository.
- **Set up PHP**: the `shivammathur/setup-php` action configures PHP 8.2.
- **Check PHP syntax**: every `*.php` file outside `vendor/` is passed through `php -l`.

### PHP Composer (`php.yml`)

This workflow triggers when `composer.json`, `composer.lock`, or a PHP file changes.

- **Checkout code**: the `actions/checkout` action retrieves the repository.
- **Validate**: `composer validate --strict` checks the dependency files.
- **Cache packages**: the `actions/cache` action restores the `vendor` folder.
- **Install dependencies**: `composer install` installs the packages, including PHPUnit.

### Build artifact (`artifact.yml`)

This workflow triggers on pushes to the main branch, except for changes that only touch `tests/` or Markdown files.

- **Checkout code**: the repository is checked out.
- **Create release folder**: the project is copied into a `release/` folder, leaving out `.git`, `.github`, `node_modules`, and `vendor`.
- **Zip project**: the folder is compressed into `expense-tracker.zip`.
- **Upload artifact**: the `actions/upload-artifact` action makes the zip available for download for seven days.

### Limitation

The PHPUnit tests are not run by the pipeline yet. The step exists in `php.yml` but is still commented out, so a failing test would not currently block a change. Enabling it is the first item in Future work.
