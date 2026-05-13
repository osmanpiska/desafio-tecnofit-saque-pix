<?php

declare(strict_types=1);

namespace App\Controller;

use duncan3dc\Laravel\BladeInstance;
use Hyperf\Codec\Json;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use OpenApi\Generator;

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
    public function index()
    {
        $paths = [BASE_PATH . '/app'];
        $openapi = Generator::scan($paths);

        return $this->response->json(Json::decode($openapi->toJson()));
    }

    #[GetMapping(path: '/swagger/ui')]
    public function ui()
    {
        $html = $this->blade->render('swagger.ui', [
            'title' => 'Saque PIX API - Swagger UI',
            'jsonUrl' => '/swagger',
        ]);

        return $this->response->html($html);
    }
}
