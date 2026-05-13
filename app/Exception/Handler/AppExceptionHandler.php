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
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
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

        if ($this->isClientError($throwable)) {
            $statusCode = $this->resolveStatusCode($throwable);

            return $response->withStatus($statusCode)
                ->withHeader('Content-Type', 'application/json')
                ->withBody(new SwooleStream(json_encode([
                    'code' => $statusCode,
                    'message' => $throwable->getMessage(),
                ])));
        }

        $message = sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile());
        $trace = $throwable->getTraceAsString();

        // Log no arquivo
        $this->fileLogger->error($message);
        $this->fileLogger->error($trace);

        // Log no console (docker logs)
        $this->stdoutLogger->error($message);
        $this->stdoutLogger->error($trace);

        $data = [
            'code' => ErrorCode::SERVER_ERROR,
            'message' => ErrorCode::getMessage(ErrorCode::SERVER_ERROR),
            'errors' => ['server' => 'An unexpected error occurred. Please try again later.'],
        ];

        return $response->withStatus(ErrorCode::SERVER_ERROR)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode($data)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    private function isClientError(Throwable $throwable): bool
    {
        return $throwable instanceof InvalidArgumentException
            || ($throwable instanceof RuntimeException && in_array($throwable->getCode(), [400, 404], true));
    }

    private function resolveStatusCode(Throwable $throwable): int
    {
        $code = $throwable->getCode();

        return in_array($code, [400, 404], true) ? $code : 400;
    }
}
