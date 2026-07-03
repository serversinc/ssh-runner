# Changelog

All notable changes to `ssh-runner` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.1] - 2026-07-03

### Fixed
- Jump host connections now actually authenticate: `SshConnection` previously called Spatie SSH's `->useJumpHost()`, which only emits a bare `-J {host}` — OpenSSH never applies `-i`/the resolved identity to that hop, so it silently fell back to whatever SSH identity happened to be ambiently available (an agent, or a matching `~/.ssh/config` entry) on the machine running the connection. `SshConnection` now routes the jump hop through an explicit `ProxyCommand` carrying the same resolved key, with `IdentitiesOnly=yes` so an ambient ssh-agent offering other keys first can't exhaust the jump host's `MaxAuthTries` before the correct key is tried. Falls back to the previous `-J` behaviour when only a password (no key) is available. `$jumpHost` may be `"host"` or `"user@host"` — a bare host defaults to the primary connection's user.
- `LocalhostSsh` redeclared `Spatie\Ssh\Ssh`'s `$password` property as `readonly`, which PHP rejects once the parent's own declaration is non-readonly — a fatal error on class load, independent of whether the class was ever instantiated. Removed the redundant redeclaration; the value is passed straight to `parent::__construct()`.

## [0.3.0] - 2026-06-13

### Added
- `getSshJumpHost(): ?string` method on the `SshServer` interface for SSH jump host (bastion) support
- `SshConnection` now calls `->useJumpHost()` on the underlying Spatie SSH instance when a jump host is configured
- Unit tests covering jump host wiring in `SshConnectionTest`

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
