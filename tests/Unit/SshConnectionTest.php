<?php

namespace Serversinc\SshRunner\Tests\Unit;

use ReflectionProperty;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\Tests\TestCase;
use Spatie\Ssh\Ssh;

class SshConnectionTest extends TestCase
{
    private function makeServer(
        ?string $jumpHost = null,
        ?string $keyPath = '/tmp/id_rsa',
        ?string $password = null,
    ): SshServer {
        return new class($jumpHost, $keyPath, $password) implements SshServer
        {
            public function __construct(
                private readonly ?string $jumpHost,
                private readonly ?string $keyPath,
                private readonly ?string $password,
            ) {}

            public function getSshHost(): string
            {
                return '10.0.1.10';
            }

            public function getSshPort(): int
            {
                return 22;
            }

            public function getSshUser(): string
            {
                return 'root';
            }

            public function getSshKeyPath(): ?string
            {
                return $this->keyPath;
            }

            public function getSshKeyContents(): ?string
            {
                return null;
            }

            public function getSshPassword(): ?string
            {
                return $this->password;
            }

            public function getSshJumpHost(): ?string
            {
                return $this->jumpHost;
            }
        };
    }

    private function getSshInstance(SshConnection $connection): Ssh
    {
        $prop = new ReflectionProperty(SshConnection::class, 'ssh');
        $prop->setAccessible(true);

        return $prop->getValue($connection);
    }

    private function extraOptions(Ssh $ssh): array
    {
        $options = new ReflectionProperty(Ssh::class, 'extraOptions');
        $options->setAccessible(true);

        return $options->getValue($ssh);
    }

    private function findProxyCommand(array $extraOptions): ?string
    {
        foreach ($extraOptions as $option) {
            if (is_string($option) && str_starts_with($option, '-o ProxyCommand=')) {
                return $option;
            }
        }

        return null;
    }

    /** @test */
    public function it_does_not_set_jump_host_when_none_configured(): void
    {
        $connection = SshConnection::for($this->makeServer());
        $extraOptions = $this->extraOptions($this->getSshInstance($connection));

        $this->assertArrayNotHasKey('jump_host', $extraOptions);
        $this->assertNull($this->findProxyCommand($extraOptions));
    }

    /** @test */
    public function it_routes_the_jump_hop_through_a_proxy_command_carrying_the_same_key(): void
    {
        $connection = SshConnection::for($this->makeServer('root@proxmox.example.com'));
        $extraOptions = $this->extraOptions($this->getSshInstance($connection));

        // The old bare `-J` never reached the jump hop's own auth — assert
        // it's gone, in favour of an explicit ProxyCommand.
        $this->assertArrayNotHasKey('jump_host', $extraOptions);

        $proxyCommand = $this->findProxyCommand($extraOptions);

        $this->assertNotNull($proxyCommand);
        $this->assertStringContainsString('/tmp/id_rsa', $proxyCommand);
        $this->assertStringContainsString('-W %h:%p', $proxyCommand);
        $this->assertStringContainsString('root@proxmox.example.com', $proxyCommand);
        // Without this, an ambient ssh-agent offering other keys first can
        // exhaust the jump host's MaxAuthTries before this key is ever tried.
        $this->assertStringContainsString('-o IdentitiesOnly=yes', $proxyCommand);
    }

    /** @test */
    public function it_defaults_the_jump_hop_user_to_the_primary_connection_user(): void
    {
        // Bare host, no "user@" prefix.
        $connection = SshConnection::for($this->makeServer('proxmox.example.com'));
        $proxyCommand = $this->findProxyCommand($this->extraOptions($this->getSshInstance($connection)));

        $this->assertNotNull($proxyCommand);
        $this->assertStringContainsString('root@proxmox.example.com', $proxyCommand);
    }

    /** @test */
    public function it_falls_back_to_bare_jump_host_when_no_key_is_available(): void
    {
        // Password-only auth: no key to carry through a ProxyCommand.
        $connection = SshConnection::for($this->makeServer(
            jumpHost: 'root@proxmox.example.com',
            keyPath: null,
            password: 'secret',
        ));
        $extraOptions = $this->extraOptions($this->getSshInstance($connection));

        $this->assertNull($this->findProxyCommand($extraOptions));
        $this->assertSame('-J root@proxmox.example.com', $extraOptions['jump_host']);
    }
}
