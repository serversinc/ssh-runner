<?php

namespace Serversinc\SshRunner\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Serversinc\SshRunner\Contracts\SshServer;

/**
 * SSH Server configuration that reads from environment variables
 * Used for Docker container integration testing
 */
class TestSshServer extends Model implements SshServer
{
    protected $table = 'test_servers';

    protected $fillable = [
        'name',
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_key_path',
        'ssh_key_contents',
    ];

    public static function fromEnvironment(): self
    {
        return new self([
            'name' => 'Docker Test Server',
            'ip_address' => getenv('SSH_TEST_HOST') ?: 'localhost',
            'ssh_port' => (int) (getenv('SSH_TEST_PORT') ?: 2222),
            'ssh_user' => getenv('SSH_TEST_USER') ?: 'testuser',
            'ssh_key_path' => getenv('SSH_TEST_KEY_PATH') ?: null,
            'ssh_key_contents' => null,
        ]);
    }

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
