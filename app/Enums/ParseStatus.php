<?php

namespace App\Enums;

enum ParseStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Running], true);
    }

    public function hasData(): bool
    {
        return in_array($this, [self::Completed, self::Partial], true);
    }
}
