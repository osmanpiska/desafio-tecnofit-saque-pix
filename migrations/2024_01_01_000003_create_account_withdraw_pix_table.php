<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAccountWithdrawPixTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_withdraw_pix', function (Blueprint $table) {
            $table->string('account_withdraw_id', 36)->primary()->comment('UUID do saque (relacionamento 1:1)');
            $table->string('type', 50)->comment('Tipo da chave PIX: email, cpf, cnpj, celular, aleatoria');
            $table->string('key', 255)->comment('Valor da chave PIX');
            $table->dateTime('created_at')->useCurrent()->comment('Data de criação');

            // Índices
            $table->index('key', 'idx_pix_key');
            $table->index('type', 'idx_pix_type');

            // Foreign key
            $table->foreign('account_withdraw_id')
                ->references('id')
                ->on('account_withdraw')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_withdraw_pix');
    }
}
