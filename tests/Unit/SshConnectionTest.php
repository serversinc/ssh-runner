<?php

namespace Serversinc\SshRunner\Tests\Unit;

use ReflectionProperty;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\SshConnection;
use Serversinc\SshRunner\Tests\TestCase;
use Spatie\Ssh\Ssh;

class SshConnectionTest extends TestCase
{
    private function makeServer(?string $jumpHost = null): SshServer
    {
        return new class($jumpHost) implements SshServer
        {
            public function __construct(private readonly ?string $jumpHost) {}

            public function getSshHost(): string { return '10.0.1.10'; }

            public function getSshPort(): int { return 22; }

            public function getSshUser(): string { return 'root'; }

            public function getSshKeyPath(): ?string { return '/tmp/id_rsa'; }

            public function getSshKeyContents(): ?string { return null; }

            public function getSshPassword(): ?string { return null; }

            public function getSshJumpHost(): ?string { return $this->jumpHost; }
        };
    }

    private function getSshInstance(SshConnection $connection): Ssh
    {
        $prop = new ReflectionProperty(SshConnection::class, 'ssh');
        $prop->setAccessible(true);

        return $prop->getValue($connection);
    }

    /** @test */
    public function it_does_not_set_jump_host_when_none_configured(): void
    {
        $connection = SshConnection::for($this->makeServer());
        $ssh = $this->getSshInstance($connection);

        $options = new ReflectionProperty(Ssh::class, 'extraOptions');
        $options->setAccessible(true);

        $this->assertArrayNotHasKey('jump_host', $options->getValue($ssh));
    }

    /** @test */
    public function it_sets_jump_host_when_configured(): void
    {
        $connection = SshConnection::for($this->makeServer('root@proxmox.example.com'));
        $ssh = $this->getSshInstance($connection);

        $options = new ReflectionProperty(Ssh::class, 'extraOptions');
        $options->setAccessible(true);

        $this->assertSame('-J root@proxmox.example.com', $options->getValue($ssh)['jump_host']);
    }
}
