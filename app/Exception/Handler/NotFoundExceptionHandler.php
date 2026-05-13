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
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class NotFoundExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        $data = [
            'code' => ErrorCode::NOT_FOUND,
            'message' => ErrorCode::getMessage(ErrorCode::NOT_FOUND),
            'errors' => ['resource' => 'The requested resource was not found.'],
        ];

        return $response->withStatus(ErrorCode::NOT_FOUND)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode($data)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof NotFoundHttpException;
    }
}
