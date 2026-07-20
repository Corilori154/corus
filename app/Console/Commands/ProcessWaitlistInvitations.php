<?php

namespace App\Console\Commands;

use App\Services\WaitlistService;
use Illuminate\Console\Command;

class ProcessWaitlistInvitations extends Command
{
    protected $signature = 'waitlist:process';
    protected $description = 'Expire les invitations de liste d’attente et invite les personnes suivantes';

    public function handle(WaitlistService $waitlist): int
    {
        $count = $waitlist->processExpiredInvitations();
        $this->info("{$count} cours avec invitation expirée ont été traités.");

        return self::SUCCESS;
    }
}
