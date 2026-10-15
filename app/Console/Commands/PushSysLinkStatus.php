<?php

namespace App\Console\Commands;

use App\Services\SysLinkNotifier;
use Illuminate\Console\Command;

class PushSysLinkStatus extends Command
{
    /**
     * php artisan syslink:push
     */
    protected $signature = 'syslink:push';

    protected $description = 'Send beacon/siren status to Uplink Sentinel SysLink';

    public function handle(SysLinkNotifier $sysLink): int
    {
        if (! SysLinkNotifier::connection()) {
            // Not configured yet is normal, not a failure worth alerting on every minute.
            $this->line('SysLink is not configured, skipping.');
            return self::SUCCESS;
        }

        $error = $sysLink->push();
        if ($error !== null) {
            $this->error($error);
            return self::FAILURE;
        }

        $this->info('Status sent to Sentinel.');
        return self::SUCCESS;
    }
}
