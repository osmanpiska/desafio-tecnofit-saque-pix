<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;
use Ramsey\Uuid\Uuid;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Conta para testes de saque imediato (saldo alto)
        $account1 = Uuid::uuid4()->toString();
        Db::table('account')->insert([
            'id' => $account1,
            'name' => 'Conta Teste - Saldo Alto',
            'balance' => 10000.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Conta para testes de saldo insuficiente (saldo baixo)
        $account2 = Uuid::uuid4()->toString();
        Db::table('account')->insert([
            'id' => $account2,
            'name' => 'Conta Teste - Saldo Baixo',
            'balance' => 50.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Conta para testes de concorrência (saldo exato para 100 saques de R$ 1,00)
        $account3 = Uuid::uuid4()->toString();
        Db::table('account')->insert([
            'id' => $account3,
            'name' => 'Conta Teste - Concorrência',
            'balance' => 100.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Conta para testes de saque agendado
        $account4 = Uuid::uuid4()->toString();
        Db::table('account')->insert([
            'id' => $account4,
            'name' => 'Conta Teste - Agendamento',
            'balance' => 500.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo "4 contas de teste criadas com sucesso!\n";
        echo "IDs:\n";
        echo "  - Conta Saldo Alto: {$account1} (saldo: R$ 10.000,00)\n";
        echo "  - Conta Saldo Baixo: {$account2} (saldo: R$ 50,00)\n";
        echo "  - Conta Concorrência: {$account3} (saldo: R$ 100,00)\n";
        echo "  - Conta Agendamento: {$account4} (saldo: R$ 500,00)\n";
    }
}
