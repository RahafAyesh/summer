<?php

namespace App\Enums;

enum AiActionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
}
