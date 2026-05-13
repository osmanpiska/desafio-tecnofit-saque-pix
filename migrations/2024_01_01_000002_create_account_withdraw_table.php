<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountWithdrawTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_withdraw', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('UUID do saque');
            $table->string('account_id', 36)->comment('UUID da conta');
            $table->string('method', 50)->comment('Método de saque: PIX, TED, etc');
            $table->decimal('amount', 15, 2)->comment('Valor do saque');
            $table->boolean('scheduled')->default(false)->comment('É saque agendado?');
            $table->dateTime('scheduled_for')->nullable()->comment('Data/hora do agendamento (null = imediato)');
            $table->boolean('done')->default(false)->comment('Saque foi processado?');
            $table->boolean('error')->default(false)->comment('Ocorreu erro no processamento?');
            $table->string('error_reason', 255)->nullable()->comment('Motivo do erro');
            $table->boolean('processing')->default(false)->comment('Está em processamento (lock)?');
            $table->dateTime('processed_at')->nullable()->comment('Data/hora do processamento');
            $table->dateTime('created_at')->useCurrent()->comment('Data de criação');
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Data de atualização');

            // Índices
            $table->index('account_id', 'idx_withdraw_account_id');
            $table->index(
                ['scheduled', 'done', 'error', 'processing', 'scheduled_for'],
                'idx_withdraw_cron_query'
            );
            $table->index('created_at', 'idx_withdraw_created_at');

            // Foreign key
            $table->foreign('account_id')
                ->references('id')
                ->on('account')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_withdraw');
    }
}
