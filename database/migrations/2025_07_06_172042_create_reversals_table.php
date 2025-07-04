<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions'); // Transação original que foi revertida
            $table->foreignId('reversed_by_user_id')->nullable()->constrained('users'); // Quem solicitou (se aplicável)
            $table->text('reason')->nullable();
            $table->timestamp('reversed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversals');
    }
};
