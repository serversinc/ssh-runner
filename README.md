# SSH Runner for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/serversinc/ssh-runner.svg?style=flat-square)](https://packagist.org/packages/serversinc/ssh-runner)
[![Total Downloads](https://img.shields.io/packagist/dt/serversinc/ssh-runner.svg?style=flat-square)](https://packagist.org/packages/serversinc/ssh-runner)
![GitHub Actions](https://github.com/serversinc/ssh-runner/actions/workflows/main.yml/badge.svg)

A pipeline-based SSH runner for Laravel that executes commands on remote servers with support for action composition, failure strategies, automatic rollback, and execution logging.

This package provides a fluent API for building SSH command pipelines using the [Spatie SSH](https://github.com/spatie/ssh) library under the hood.

## Installation

You can install the package via Composer:

```bash
composer require serversinc/ssh-runner
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Serversinc\SshRunner\SshRunnerServiceProvider" --tag="ssh-runner-config"
```

Publish the migrations:

```bash
php artisan vendor:publish --provider="Serversinc\SshRunner\SshRunnerServiceProvider" --tag="ssh-runner-migrations"
```

Run the migrations to create the logging tables:

```bash
php artisan migrate
```

### Note: UUID / ULID Primary Keys

The package's migration uses `$table->morphs('server')` which creates `server_id` as an `unsignedBigInteger`. If your server model uses UUID or ULID primary keys, create a migration in your application to change the column type:

```php
Schema::table('ssh_pipeline_logs', function (Blueprint $table) {
    $table->string('server_id')->change();
});
```

## Basic Usage

### 1. Implement the SshServer Interface

Your server model must implement the `SshServer` contract:

```php
use Serversinc\SshRunner\Contracts\SshServer;

class Server extends Model implements SshServer
{
    public function getSshHost(): string
    {
        return $this->ip_address;
    }

    public function getSshPort(): int
    {
        return $this->ssh_port ?? 22;
    }

    public function getSshUser(): string
    {
        return $this->ssh_user;
    }

    public function getSshKeyPath(): ?string
    {
        return $this->ssh_key_path;
    }

    public function getSshKeyContents(): ?string
    {
        return $this->ssh_key_contents;
    }
}
```

### 2. Create an Action

Actions are reusable, testable units of work:

```php
use Serversinc\SshRunner\Actions\BaseSshAction;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

class InstallPackage extends BaseSshAction
{
    public function __construct(private string $packageName)
    {
    }

    public function handle(SshServer $server, Ssh $ssh): ActionResult
    {
        return $this->run($ssh, ["apt-get install -y {$this->packageName}"]);
    }

    public function undo(SshServer $server, Ssh $ssh): void
    {
        // Called automatically on rollback
        $ssh->execute(["apt-get remove -y {$this->packageName}"]);
    }
}
```

### 3. Execute a Pipeline

There are several ways to execute pipelines:

#### Using the Facade (Recommended)

```php
use SshRunner;
use Serversinc\SshRunner\Enums\FailureStrategy;

$result = SshRunner::pipeline($server)
    ->run(new UpdatePackageList)
    ->run(new InstallPackage('nginx'))
    ->run(new InstallPackage('nginx'))
    ->run(new RestartService('nginx'))
    ->execute();

if ($result->success) {
    echo "Pipeline completed in {$result->duration()} seconds";
} else {
    foreach ($result->failedActions() as $action) {
        echo "Failed: {$action->action}\n";
        echo "Error: {$action->errorOutput}\n";
    }
}
```

#### Using SshConnection

```php
use Serversinc\SshRunner\SshConnection;

$connection = SshConnection::for($server);

$result = $connection->pipeline()
    ->run(new UpdatePackageList)
    ->run(new InstallPackage('nginx'))
    ->execute();
```

#### Using the Factory Class

```php
use Serversinc\SshRunner\SshRunner;

$result = SshRunner::pipeline($server)
    ->run(new UpdatePackageList)
    ->run(new InstallPackage('nginx'))
    ->execute();

// Or execute a script directly
$result = SshRunner::script($server, new DeployWordPressSite(
    path: '/var/www/example.com',
    domain: 'example.com',
    dbName: 'wordpress_example',
    dbUser: 'wp_example',
    dbPassword: 'secure-password',
));
```

## Failure Strategies

Control what happens when an action fails:

```php
use Serversinc\SshRunner\Enums\FailureStrategy;

// STOP (default) - Stop execution on first failure
$pipeline->onFailure(FailureStrategy::STOP);

// CONTINUE - Keep executing remaining actions
$pipeline->onFailure(FailureStrategy::CONTINUE);

// ROLLBACK - Undo completed actions in reverse order
$pipeline->onFailure(FailureStrategy::ROLLBACK)
    ->run(new CreateDatabase)
    ->run(new CreateUser) // If this fails, CreateDatabase->undo() is called
    ->execute();
```

## Execution Logging

All pipeline runs are automatically logged to the database:

```php
use Serversinc\SshRunner\Models\SshPipelineLog;

// Get all runs for a server
$runs = SshPipelineLog::where('server_id', $server->id)->get();

// Check if a specific run failed
$run = SshPipelineLog::find(1);
if ($run->failed()) {
    foreach ($run->actionLogs as $log) {
        echo "{$log->action}: {$log->exit_code}\n";
    }
}
```

## Single Action Execution

Execute a single action without the pipeline:

```php
// Using the Facade
$result = SshRunner::run($server, new UpdatePackageList);

// Or using SshConnection
$connection = SshConnection::for($server);
$result = $connection->execute(new UpdatePackageList);

if ($result->success) {
    echo $result->output;
} else {
    echo $result->errorOutput;
}
```

## Script Execution

Scripts allow you to group multiple related commands into a single action with built-in step-by-step execution, optional rollback per step, and critical/non-critical step handling.

### Creating a Script

Extend `BaseScript` and define your steps:

```php
use Serversinc\SshRunner\Scripts\BaseScript;
use Serversinc\SshRunner\Scripts\ScriptStep;

class DeployWordPressSite extends BaseScript
{
    public function __construct(
        private string $path,
        private string $domain,
        private string $dbName,
        private string $dbUser,
        private string $dbPassword,
    ) {}

    public function steps(): array
    {
        return [
            new ScriptStep(
                name: 'Create application directory',
                command: "mkdir -p {$this->path}",
                rollback: "rm -rf {$this->path}",
            ),
            new ScriptStep(
                name: 'Download WordPress',
                command: "cd {$this->path} && wget https://wordpress.org/latest.tar.gz",
                rollback: "rm -f {$this->path}/latest.tar.gz",
            ),
            new ScriptStep(
                name: 'Extract archive',
                command: "cd {$this->path} && tar -xzf latest.tar.gz",
            ),
            new ScriptStep(
                name: 'Create database',
                command: "mysql -e \"CREATE DATABASE IF NOT EXISTS {$this->dbName};\"",
                rollback: "mysql -e \"DROP DATABASE IF EXISTS {$this->dbName};\"",
            ),
            new ScriptStep(
                name: 'Create database user',
                command: "mysql -e \"CREATE USER IF NOT EXISTS '{$this->dbUser}'@'localhost' IDENTIFIED BY '{$this->dbPassword}'; GRANT ALL PRIVILEGES ON {$this->dbName}.* TO '{$this->dbUser}'@'localhost'; FLUSH PRIVILEGES;\"",
                rollback: "mysql -e \"DROP USER IF EXISTS '{$this->dbUser}'@'localhost';\"",
            ),
            new ScriptStep(
                name: 'Set permissions',
                command: "chown -R www-data:www-data {$this->path}",
                critical: false, // Non-critical: failure here won't stop the script
            ),
        ];
    }

    public function validate(): void
    {
        if (empty($this->path) || empty($this->domain)) {
            throw new \InvalidArgumentException('Path and domain are required');
        }
    }
}
```

### Using Scripts in Pipelines

Scripts work seamlessly inside pipelines:

```php
use SshRunner;

$result = SshRunner::pipeline($server)
    ->run(new UpdatePackageList)
    ->script(new DeployWordPressSite(
        path: '/var/www/example.com',
        domain: 'example.com',
        dbName: 'wordpress_example',
        dbUser: 'wp_example',
        dbPassword: 'secure-password',
    ))
    ->run(new RestartService('nginx'))
    ->onFailure(FailureStrategy::ROLLBACK)
    ->execute();
```

### Executing a Script Directly

Run a script as a single action without a pipeline:

```php
use SshRunner;

$result = SshRunner::script($server, new DeployWordPressSite(
    path: '/var/www/example.com',
    domain: 'example.com',
    dbName: 'wordpress_example',
    dbUser: 'wp_example',
    dbPassword: 'secure-password',
));

if ($result->success) {
    echo $result->output;
} else {
    echo $result->errorOutput;
}
```

### Script Behavior

- **SSH connection reuse** — Scripts automatically enable SSH multiplexing (`ControlMaster=auto`) so all steps share the same underlying TCP connection, avoiding repeated authentication overhead.
- **Critical steps** (`critical: true`, the default) trigger automatic rollback of all previously completed steps on failure.
- **Non-critical steps** (`critical: false`) log a failure but continue to the next step.
- **Step-level rollback** commands are executed in reverse order when a critical step fails or when the pipeline's `ROLLBACK` failure strategy is triggered.
- **Filesystem state persists** between steps (files created in one step are available in the next).
- Scripts integrate with the existing logging and failure strategy infrastructure.

> **Note:** While the SSH network connection is reused between steps, each `ScriptStep` still runs in its own shell process. This means environment variables set in one step (e.g. `export VAR=value`) are not available in subsequent steps. If you need to share data between steps, use files on the remote filesystem.

## Advanced Usage

### Creating a Connection for Reuse

```php
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\SshPipeline;

$connection = SshConnection::for($server);

// Execute multiple pipelines on the same connection
$result1 = $connection->pipeline()
    ->run(new Action1())
    ->execute();

$result2 = $connection->pipeline()
    ->run(new Action2())
    ->execute();
```

### Using SshRunner Factory Methods

```php
use Serversinc\SshRunner\SshRunner;

// Create a connection
$connection = SshRunner::connect($server);

// Create a pipeline directly
$pipeline = SshRunner::pipeline($server);

// Execute a single action
$result = SshRunner::run($server, new SomeAction());

// Execute a script directly
$result = SshRunner::script($server, new DeployWordPressSite(
    path: '/var/www/example.com',
    domain: 'example.com',
    dbName: 'wordpress_example',
    dbUser: 'wp_example',
    dbPassword: 'secure-password',
));
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please use the [issue tracker](https://github.com/serversinc/ssh-runner/issues) and mark it as a security concern.

## Credits

- [Max Diamond](https://github.com/serversinc)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
