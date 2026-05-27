<?php

namespace Serversinc\SshRunner;

use Serversinc\SshRunner\Contracts\SshActionInterface;
use Serversinc\SshRunner\Contracts\SshServer;
use Serversinc\SshRunner\Results\ActionResult;
use Spatie\Ssh\Ssh;

class SshConnection
{
    private readonly Ssh|LocalhostSsh $ssh;

    private function __construct(private readonly SshServer $server)
    {
        $this->ssh = $this->build();
    }

    public static function for(SshServer $server): static
    {
        return new static($server);
    }

    /**
     * @throws \Exception
     */
    private function build(): Ssh|LocalhostSsh
    {
        $host = $this->server->getSshHost();
        $port = $this->server->getSshPort();
        $user = $this->server->getSshUser();
        $password = $this->server->getSshPassword();

        $hasKeyPath = (bool) $this->server->getSshKeyPath();
        $hasKeyContents = (bool) $this->server->getSshKeyContents();
        $hasPassword = (bool) $password;

        // For localhost connections, use our custom wrapper that forces SSH
        // instead of letting Spatie SSH bypass it
        if (in_array($host, ['localhost', '127.0.0.1'])) {
            $keyPath = $this->server->getSshKeyPath();

            if (! $keyPath && $hasKeyContents) {
                $keyPath = $this->writeTempKey($this->server->getSshKeyContents() ?? '');
            }

            return new LocalhostSsh($user, $host, $port, $keyPath, $password);
        }

        $ssh = Ssh::create($user, $host, $port, $password);

        if ($keyPath = $this->server->getSshKeyPath()) {
            $ssh->usePrivateKey($keyPath);
        } elseif ($keyContents = $this->server->getSshKeyContents()) {
            $tempPath = $this->writeTempKey($keyContents);
            $ssh->usePrivateKey($tempPath);
        } elseif (! $hasPassword) {
            throw new SshConnectionException('No SSH key or password provided.');
        }

        $ssh->disableStrictHostKeyChecking();

        return $ssh;
    }

    private function writeTempKey(string $contents): string
    {
        $path = sys_get_temp_dir().'/'.uniqid('ssh_key_', true);
        file_put_contents($path, $contents);
        chmod($path, 0600);

        register_shutdown_function(fn (): bool => @unlink($path));

        return $path;
    }

    public function execute(SshActionInterface $action): ActionResult
    {
        return $action->handle($this->server, $this->ssh);
    }

    public function pipeline(): SshPipeline
    {
        return SshPipeline::for($this->server, $this->ssh);
    }
}
