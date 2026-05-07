<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Contracts;

interface SshServer
{
    public function getSshHost(): string;

    public function getSshPort(): int;

    public function getSshUser(): string;

    public function getSshKeyPath(): ?string;

    public function getSshKeyContents(): ?string;
}
