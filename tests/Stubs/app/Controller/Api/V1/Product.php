<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Controller\Api\V1;
use Tests\Stubs\app\Controller\ApiBase;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;

#[Group('open-api/v1')]
class Product extends ApiBase
{
    #[Route('all-product', 'get')]
    public function product(?string $nextId = null, int $limit = 100)
    {
    }

    #[Route('upload-image', 'post')]
    public function uploadImage()
    {
    }
}
