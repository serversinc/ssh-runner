<?php

namespace Serversinc\SshRunner;

use Spatie\Ssh\Ssh;
use Symfony\Component\Process\Process;

/**
 * SSH wrapper that forces SSH connections even for localhost
 *
 * The Spatie SSH library bypasses SSH for localhost/127.0.0.1 and runs commands
 * directly on the local machine. This wrapper extends Ssh but overrides the
 * getExecuteCommand method to always use SSH, even for localhost connections.
 */
class LocalhostSsh extends Ssh
{
    private array $extraSshOptions = [];

    public function __construct(
        private readonly string $actualUser,
        private readonly string $actualHost,
        private readonly int $actualPort,
        private readonly ?string $privateKeyPath,
        private readonly ?string $password = null,
    ) {
        // Call parent with fake hostname to bypass localhost check
        parent::__construct($this->actualUser, 'docker-container.local', $this->actualPort, $this->password);

        // Set up SSH options
        if ($this->privateKeyPath) {
            $this->usePrivateKey($this->privateKeyPath);
        }

        $this->addExtraOption('-o StrictHostKeyChecking=no');
        $this->addExtraOption('-o UserKnownHostsFile=/dev/null');

        // Store the options for later use
        $this->extraSshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
        ];

        if ($this->privateKeyPath) {
            array_unshift($this->extraSshOptions, '-i '.$this->privateKeyPath);
        }

        if ($this->actualPort !== 22) {
            $this->extraSshOptions[] = '-p '.$this->actualPort;
        }
    }

    /**
     * Override to always return SSH command, never local execution
     */
    #[\Override]
    public function getExecuteCommand($command): string
    {
        $commands = is_array($command) ? $command : [$command];
        $commandString = implode(PHP_EOL, $commands);

        $extraOptions = implode(' ', $this->extraSshOptions);
        $target = "{$this->actualUser}@{$this->actualHost}";

        $passwordCommand = $this->getPasswordCommand();
        $delimiter = 'EOF-SPATIE-SSH';

        return "{$passwordCommand}ssh {$extraOptions} {$target} 'bash -se' << '{$delimiter}'".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }

    /**
     * Override execute to use our custom command
     */
    #[\Override]
    public function execute($command): Process
    {
        $sshCommand = $this->getExecuteCommand($command);

        $process = Process::fromShellCommandline($sshCommand);
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
