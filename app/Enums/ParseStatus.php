<?php

namespace App\Enums;

enum ParseStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Running], true);
    }
}
