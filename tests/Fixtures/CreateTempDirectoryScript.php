<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Tests\Fixtures;

use Serversinc\SshRunner\Scripts\BaseScript;
use Serversinc\SshRunner\Scripts\ScriptStep;

/**
 * A simple test script that creates a temp directory and file
 */
class CreateTempDirectoryScript extends BaseScript
{
    private string $dir;
    private string $file;

    public function __construct(
        private readonly string $dirName = 'ssh_runner_test',
        private readonly string $fileName = 'test.txt',
    ) {
        $this->dir = "/tmp/{$this->dirName}";
        $this->file = "{$this->dir}/{$this->fileName}";
    }

    public function steps(): array
    {
        return [
            new ScriptStep(
                name: 'Create temp directory',
                command: "mkdir -p {$this->dir}",
                rollback: "rm -rf {$this->dir}",
            ),
            new ScriptStep(
                name: 'Create temp file',
                command: "echo 'hello world' > {$this->file}",
                rollback: "rm -f {$this->file}",
            ),
            new ScriptStep(
                name: 'Verify file exists',
                command: "cat {$this->file}",
            ),
        ];
    }

    public function validate(): void
    {
        if (empty($this->dirName) || empty($this->fileName)) {
            throw new \InvalidArgumentException('Directory and file names are required');
        }
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getFile(): string
    {
        return $this->file;
    }
}
