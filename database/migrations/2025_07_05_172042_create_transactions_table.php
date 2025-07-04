<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets'); // Origem da transação (null para depósito)
            $table->foreignId('to_wallet_id')->constrained('wallets'); // Destino da transação
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['deposit', 'transfer', 'reversal_deposit', 'reversal_transfer_out', 'reversal_transfer_in']); // Tipos de transação
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('completed'); // Status da transação
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
