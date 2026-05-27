<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Scripts;

abstract class BaseScript
{
    abstract public function steps(): array;

    public function validate(): void {}
}
