<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabbyRedirectPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabby_success_page_loads(): void
    {
        $this->get('/tabby/success?payment_id=pay_1')->assertOk();
    }

    public function test_tabby_cancel_page_loads(): void
    {
        $this->get('/tabby/cancel?payment_id=pay_1')->assertOk();
    }

    public function test_tabby_failure_page_loads(): void
    {
        $this->get('/tabby/failure?payment_id=pay_1')->assertOk();
    }
}
