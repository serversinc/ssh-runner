<?php

namespace Serversinc\SshRunner\Tests\Fixtures;

use Serversinc\SshRunner\Contracts\SshServer;

/**
 * Simple SSH Server configuration for integration testing
 * Does not extend Model, so it doesn't require database persistence
 */
class IntegrationTestServer implements SshServer
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly ?string $keyPath = null,
        private readonly ?string $keyContents = null,
        private readonly ?string $password = null,
    ) {}

    public static function fromEnvironment(): self
    {
        return new self(
            host: getenv('SSH_TEST_HOST') ?: '127.0.0.1',
            port: (int) (getenv('SSH_TEST_PORT') ?: 2222),
            user: getenv('SSH_TEST_USER') ?: 'testuser',
            keyPath: getenv('SSH_TEST_KEY_PATH') ?: 'docker/ssh-keys/id_rsa'
        );
    }

    public function getSshHost(): string
    {
        return $this->host;
    }

    public function getSshPort(): int
    {
        return $this->port;
    }

    public function getSshUser(): string
    {
        return $this->user;
    }

    public function getSshKeyPath(): ?string
    {
        return $this->keyPath;
    }

    public function getSshKeyContents(): ?string
    {
        return $this->keyContents;
    }

    public function getSshPassword(): ?string
    {
        return $this->password;
    }

    public function getSshJumpHost(): ?string
    {
        return null;
    }
}
