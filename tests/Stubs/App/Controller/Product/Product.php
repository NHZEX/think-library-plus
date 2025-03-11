<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\Product;

use Tests\Stubs\App\Controller\ApiBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Route\Annotation\ResourceRule;

#[Resource('product')]
#[AuthDesc([
    'product' => ['产品管理', 20],
    'product.read' => ['访问产品', 111],
    'product.create' => ['创建产品', 112],
    'product.edit' => ['编辑产品', 113],
    'product.delete' => ['删除产品', 114],
])]
class Product extends ApiBase
{
    #[Auth('product.read')]
    public function index(int $current = 1, int $size = 1)
    {
    }

    #[Auth('product.read')]
    public function read(int $id)
    {
    }

    #[Auth('product.create')]
    public function save()
    {
    }

    #[Auth('product.edit')]
    public function update(int $id)
    {
    }

    #[Auth('product.delete')]
    public function delete(int $id)
    {
    }

    #[Auth('product.edit')]
    #[ResourceRule('color-image-prepare', method: 'POST')]
    public function productImageSave()
    {
    }

    #[Auth('product.download.product-image')]
    #[ResourceRule('download-all-image', method: 'POST')]
    public function downloadAllImage()
    {
    }

    #[Auth([
        'product.rebuild-product',
        'product.list-rebuild-product',
    ])]
    #[ResourceRule('list-rebuild', method: 'GET')]
    public function listRebuildProduct()
    {
    }

    #[Auth('product.statistics')]
    #[ResourceRule('statistics-create-count-by-style-and-created-date-group', method: 'GET')]
    public function statisticsCreateCountByStyleAndCreatedDateGroup()
    {
    }

    #[Auth('product.export-listing')]
    #[ResourceRule('export-listing$', method: 'POST')]
    public function exportListing()
    {
    }

    #[Auth('product.export-listing')]
    #[ResourceRule('export-listing/job-download$', method: 'GET')]
    public function exportListingJobDownload(int $id)
    {
    }
}
