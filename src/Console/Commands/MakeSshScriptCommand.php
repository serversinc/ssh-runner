<?php

namespace Serversinc\SshRunner\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeSshScriptCommand extends GeneratorCommand
{
    protected $signature = 'ssh:script {name}';

    protected $description = 'Create a new SSH script class';

    protected $type = 'SSH Script';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/ssh-script.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\SSH\\Scripts';
    }
}
