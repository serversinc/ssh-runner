# Changelog

All notable changes to `ssh-runner` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-05-27

### Added
- Artisan generator commands `ssh:action` and `ssh:script` for scaffolding Actions and Scripts
- Script support with step-by-step execution, rollback, and critical/non-critical steps
- `BaseScript` and `ScriptStep` classes for multi-step SSH operations
- `ExecuteScriptAction` for running scripts within pipelines

### Fixed
- CI dependency resolution for Laravel 11 by widening `illuminate/support` and `phpunit/phpunit` constraints

## [0.0.1] - 2026-05-07

### Added
- Initial release of SSH Runner for Laravel
- Pipeline-based SSH command execution
- Action composition pattern for reusable SSH commands
- Failure strategies: STOP, CONTINUE, and ROLLBACK
- Automatic rollback on failure
- Database logging for pipeline and action execution
- Fluent API for building SSH pipelines
- Laravel service provider with auto-discovery
- SshRunner Facade for easy access
- Support for Spatie SSH library
- PHPUnit test suite with Orchestra Testbench

[0.0.1]: https://github.com/serversinc/ssh-runner/releases/tag/v0.0.1
