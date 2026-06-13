<?php

namespace Serversinc\SshRunner\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Serversinc\SshRunner\Contracts\SshServer;

class FakeServer extends Model implements SshServer
{
    protected $table = 'servers';

    protected $fillable = [
        'name',
        'ip_address',
        'ssh_port',
        'ssh_user',
        'ssh_key_path',
        'ssh_key_contents',
        'ssh_password',
    ];

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

    public function getSshPassword(): ?string
    {
        return $this->ssh_password;
    }

    public function getSshJumpHost(): ?string
    {
        return null;
    }
}
