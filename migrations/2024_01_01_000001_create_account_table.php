<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('UUID da conta');
            $table->string('name', 255)->comment('Nome do titular');
            $table->decimal('balance', 15, 2)->default(0)->comment('Saldo disponível');
            $table->dateTime('created_at')->useCurrent()->comment('Data de criação');
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Data de atualização');

            // Índices
            $table->index('created_at', 'idx_account_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account');
    }
}
