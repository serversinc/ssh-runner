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

        $resolvedKeyPath = null;

        if ($keyPath = $this->server->getSshKeyPath()) {
            $resolvedKeyPath = $keyPath;
            $ssh->usePrivateKey($keyPath);
        } elseif ($keyContents = $this->server->getSshKeyContents()) {
            $resolvedKeyPath = $this->writeTempKey($keyContents);
            $ssh->usePrivateKey($resolvedKeyPath);
        } elseif (! $hasPassword) {
            throw new SshConnectionException('No SSH key or password provided.');
        }

        $ssh->disableStrictHostKeyChecking();

        if ($jumpHost = $this->server->getSshJumpHost()) {
            $this->applyJumpHost($ssh, $jumpHost, $user, $resolvedKeyPath);
        }

        return $ssh;
    }

    /**
     * Spatie\Ssh::useJumpHost() emits a bare `-J {host}`, which OpenSSH only
     * uses to route the connection — it does NOT carry -i/the resolved
     * key/password through to that hop. The jump hop then falls back to
     * whatever SSH identity happens to be ambiently available (an agent, or
     * a matching ~/.ssh/config entry) on the machine running this process,
     * which silently breaks unless that's been configured out-of-band.
     *
     * Instead, route the jump hop through an explicit ProxyCommand carrying
     * the SAME key already resolved for the primary connection, so a single
     * key (authorized on both the jump host and the destination) is enough —
     * no per-machine SSH config required.
     *
     * $jumpHost may be "host" or "user@host"; a bare host defaults to the
     * primary connection's user.
     */
    private function applyJumpHost(Ssh $ssh, string $jumpHost, string $defaultUser, ?string $keyPath): void
    {
        if ($keyPath === null) {
            // No key resolved (password-only auth) — fall back to the
            // previous behaviour; there's no clean way to carry a password
            // through a ProxyCommand hop without additional tooling.
            $ssh->useJumpHost($jumpHost);

            return;
        }

        [$jumpUser, $jumpHostname] = str_contains($jumpHost, '@')
            ? explode('@', $jumpHost, 2)
            : [$defaultUser, $jumpHost];

        // IdentitiesOnly=yes: without it, an ambient ssh-agent offering other
        // keys first can exhaust the jump host's MaxAuthTries before this
        // key is ever tried, even though it's the one that's actually
        // authorized.
        $proxyCommand = 'ssh -i '.escapeshellarg($keyPath)
            .' -o IdentitiesOnly=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null'
            .' -W %h:%p '.$jumpUser.'@'.$jumpHostname;

        $ssh->addExtraOption('-o ProxyCommand='.escapeshellarg($proxyCommand));
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
