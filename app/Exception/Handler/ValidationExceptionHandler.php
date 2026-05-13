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

namespace App\Exception\Handler;

use App\Constants\ErrorCode;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    protected LoggerInterface $fileLogger;

    protected StdoutLoggerInterface $stdoutLogger;

    public function __construct(LoggerFactory $loggerFactory, StdoutLoggerInterface $stdoutLogger)
    {
        $this->fileLogger = $loggerFactory->get('default');
        $this->stdoutLogger = $stdoutLogger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        /** @var ValidationException $throwable */
        $code = $throwable->getCode() ?: ErrorCode::VALIDATION_ERROR;
        $message = $throwable->getMessage() ?: ErrorCode::getMessage($code);
        $errors = $throwable->errors();

        // Log no arquivo
        $this->fileLogger->warning("Validation Error: {$message}", $errors);
        // Log no console
        $this->stdoutLogger->warning("Validation Error: {$message}");

        return $response->withStatus(ErrorCode::VALIDATION_ERROR)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode([
                'code' => $code,
                'message' => $message,
                'errors' => $errors,
            ])));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
