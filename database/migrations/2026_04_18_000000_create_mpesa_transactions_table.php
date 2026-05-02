<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = config('mpesa.database.table', 'mpesa_transactions');

        Schema::create($table, function (Blueprint $t) {
            $t->id();
            $t->string('merchant_request_id')->nullable()->index();
            $t->string('checkout_request_id')->nullable()->unique();
            $t->string('mpesa_receipt_number')->nullable()->unique();
            $t->string('phone', 15)->index();
            $t->unsignedInteger('amount');
            $t->string('reference')->nullable();
            $t->string('description')->nullable();
            $t->string('status', 20)->default('pending')->index();   // pending|completed|failed
            $t->string('result_code')->nullable();
            $t->string('result_desc')->nullable();
            $t->string('type', 20)->default('stk');                  // stk|c2b|b2c|reversal
            $t->json('request_payload')->nullable();
            $t->json('callback_payload')->nullable();
            $t->nullableMorphs('payer');
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('mpesa.database.table', 'mpesa_transactions'));
    }
};
