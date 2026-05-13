<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\WithdrawEmailDTO;
use duncan3dc\Laravel\BladeInstance;
use FriendsOfHyperf\Mail\Facade\Mail;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class EmailService
{
    private BladeInstance $blade;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly StdoutLoggerInterface $stdoutLogger
    ) {
        $this->blade = new BladeInstance(
            BASE_PATH . '/resources/views',
            BASE_PATH . '/runtime/views'
        );
    }

    /**
     * Envia email de saque PIX concluído
     */
    public function sendWithdrawCompleted(WithdrawEmailDTO $dto): bool
    {
        try {
            // Renderiza o template Blade
            $html = $this->blade->render('emails.withdraw_completed', $dto->toArray());

            // Envia o email usando Facade
            Mail::html($html, function ($message) use ($dto) {
                $message->to($dto->email);
                $message->subject('Saque PIX Concluído - R$ ' . number_format($dto->amount, 2, ',', '.'));
            });
            $this->closeTransport();

            // Log de sucesso (email mascarado por segurança)
            $this->logger->info('Email sent successfully', [
                'to' => $this->maskEmail($dto->email),
                'withdraw_id' => $dto->withdrawId,
                'amount' => $dto->amount,
            ]);

            $this->stdoutLogger->info("Email sent to: {$this->maskEmail($dto->email)}");

            return true;
        } catch (Throwable $e) {
            // Log de erro (email mascarado por segurança)
            $this->logger->error('Failed to send email', [
                'to' => $this->maskEmail($dto->email),
                'withdraw_id' => $dto->withdrawId,
                'error' => $e->getMessage(),
            ]);

            $this->stdoutLogger->error("Failed to send email: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Envia email de teste
     */
    public function sendTestEmail(string $toEmail): bool
    {
        $dto = new WithdrawEmailDTO(
            email: $toEmail,
            amount: 150.00,
            processedAt: new \DateTime(),
            withdrawId: 'TEST-' . uniqid(),
            pixType: \App\Enum\PixKeyType::EMAIL,
            pixKey: 'teste@email.com'
        );

        return $this->sendWithdrawCompleted($dto);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = strlen($name) > 2
            ? substr($name, 0, 2) . str_repeat('*', strlen($name) - 2)
            : str_repeat('*', strlen($name));

        return $maskedName . '@' . $domain;
    }

    private function closeTransport(): void
    {
        try {
            $transport = Mail::getSymfonyTransport();
            if (method_exists($transport, 'stop')) {
                $transport->stop();
            }
        } catch (Throwable) {
            // Fechar a conexão SMTP é uma otimização para testes e shutdown limpo.
        }
    }
}
