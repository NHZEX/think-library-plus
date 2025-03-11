<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\Product;

use Tests\Stubs\App\Controller\ApiResourceBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Route\Annotation\Resource;
use Zxin\Think\Route\Annotation\ResourceRule;

#[Resource('product/category')]
#[AuthDesc([
        'product.category' => ['产品分类', 121],
])]
class ProductCategory extends ApiResourceBase {


    public function index(int $current = 1, int $size = 1)
    {
    }

    #[ResourceRule('tree')]
    #[Auth('product.category.read')]
    public function tree()
    {
    }

    #[ResourceRule('list')]
    #[Auth('product.category.read')]
    public function list()
    {
    }

    #[Auth('product.category.read')]
    public function read()
    {
    }

    #[Auth('product.category.edit')]
    public function save()
    {
    }

    #[Auth('product.category.edit')]
    public function update()
    {
    }

    #[Auth('product.category.delete')]
    public function delete()
    {
    }
}
