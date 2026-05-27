<?php

namespace Serversinc\SshRunner\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeSshActionCommand extends GeneratorCommand
{
    protected $signature = 'ssh:action {name}';

    protected $description = 'Create a new SSH action class';

    protected $type = 'SSH Action';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/ssh-action.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\SSH\\Actions';
    }
}
