<?php

namespace App\Console\Commands;

use App\Services\EezeePayService;
use Illuminate\Console\Command;
use Throwable;

class EezeePayTestCommand extends Command
{
    protected $signature = 'eezee-pay:test
                            {action=balance : login, regenerate, categories, or balance}';

    protected $description = 'Smoke-test Eezee Pay sandbox API using configured credentials';

    public function handle(EezeePayService $eezeePay): int
    {
        $action = strtolower(trim((string) $this->argument('action')));

        try {
            $data = match ($action) {
                'login' => $eezeePay->login(),
                'regenerate' => $eezeePay->regenerateToken(),
                'categories' => $eezeePay->categories(),
                'balance' => $eezeePay->balance(),
                default => throw new \InvalidArgumentException("Unknown action \"{$action}\". Use: login, regenerate, categories, balance."),
            };
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Eezee Pay response (JSON):');
        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

        return self::SUCCESS;
    }
}
