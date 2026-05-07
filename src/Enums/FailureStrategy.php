<?php

declare(strict_types=1);

namespace Serversinc\SshRunner\Enums;

enum FailureStrategy
{
    case STOP;
    case CONTINUE;
    case ROLLBACK;
}
