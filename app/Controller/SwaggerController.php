<?php

declare(strict_types=1);

namespace App\Controller;

use duncan3dc\Laravel\BladeInstance;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class SwaggerController extends AbstractController
{
    private BladeInstance $blade;

    public function __construct()
    {
        $this->blade = new BladeInstance(
            BASE_PATH . '/resources/views',
            BASE_PATH . '/runtime/views'
        );
    }

    #[GetMapping(path: '/swagger')]
    public function index(): ResponseInterface
    {
        $file = BASE_PATH . '/storage/swagger/http.json';

        if (! file_exists($file)) {
            return $this->response->json([
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'Saque PIX API',
                    'version' => '1.0.0',
                    'description' => 'Swagger documentation was not generated yet.',
                ],
                'paths' => [],
            ]);
        }

        return $this->response
            ->raw(file_get_contents($file))
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    #[GetMapping(path: '/swagger/ui')]
    public function ui(): ResponseInterface
    {
        $html = $this->blade->render('swagger.ui', [
            'title' => 'Saque PIX API - Swagger UI',
            'jsonUrl' => '/swagger',
        ]);

        return $this->response
            ->raw($html)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
