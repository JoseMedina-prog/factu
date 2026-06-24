<?php

namespace App\Events;

use App\Models\NumberingRange;
use App\Models\Tenant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NumberingRangeAlert
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const LEVEL_WARNING = 'warning';
    public const LEVEL_CRITICAL = 'critical';

    public function __construct(
        public Tenant $tenant,
        public NumberingRange $range,
        public string $level,
        public string $message
    ) {}
}