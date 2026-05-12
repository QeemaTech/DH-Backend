<?php

namespace App\Console\Commands;

use App\Services\Like4AppService;
use Illuminate\Console\Command;

class Like4AppCheckBalance extends Command
{
    protected $signature = 'like4app:check-balance';

    protected $description = 'Show Like4App (LikeCard) account balance';

    public function handle(Like4AppService $like4App): int
    {
        try {
            $resp = $like4App->checkBalance();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $balance = data_get($resp, 'balance')
            ?? data_get($resp, 'data.balance')
            ?? data_get($resp, 'data.0.balance');
        $currency = data_get($resp, 'currency')
            ?? data_get($resp, 'data.currency')
            ?? data_get($resp, 'data.0.currency');
        $userId = data_get($resp, 'userId')
            ?? data_get($resp, 'data.userId')
            ?? data_get($resp, 'data.0.userId');

        $this->info('Like4App balance response received.');
        $this->line('userId: '.(string) ($userId ?? ''));
        $this->line('balance: '.(string) ($balance ?? ''));
        $this->line('currency: '.(string) ($currency ?? ''));

        return self::SUCCESS;
    }
}
