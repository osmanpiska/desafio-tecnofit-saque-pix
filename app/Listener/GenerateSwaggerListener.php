<?php

declare(strict_types=1);

namespace App\Listener;

use Hyperf\Codec\Json;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use OpenApi\Generator;

class GenerateSwaggerListener implements ListenerInterface
{
    public function __construct(
        private readonly ConfigInterface $config
    ) {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $this->config->get('swagger.auto_generate', false)) {
            return;
        }

        $paths = $this->config->get('swagger.scan.paths', [BASE_PATH . '/app']);
        $jsonDir = $this->config->get('swagger.json_dir', BASE_PATH . '/storage/swagger');

        if (! is_dir($jsonDir)) {
            mkdir($jsonDir, 0755, true);
        }

        $openapi = Generator::scan($paths);
        $spec = Json::decode($openapi->toJson());

        $serverConfig = $this->config->get('swagger.server.http', []);

        if (! empty($serverConfig['servers'])) {
            $spec['servers'] = $serverConfig['servers'];
        }

        if (! empty($serverConfig['info'])) {
            $spec['info'] = $serverConfig['info'];
        }

        file_put_contents(
            rtrim($jsonDir, '/') . '/http.json',
            Json::encode($spec)
        );
    }
}
