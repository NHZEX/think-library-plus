<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Controller\Picture;

use Tests\Stubs\app\Controller\ApiBase;
use Zxin\Think\Auth\Annotation\Auth;
use Zxin\Think\Auth\Annotation\AuthDesc;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;

#[Group('picture')]
#[AuthDesc([
    'picture' => ['图册管理', 19],
    'picture.read' => ['访问图片', 111],
    'picture.upload' => ['上传图片', 112],
    'workbench.quick-query-picture' => ['工作台-快速查图', 100],
    'picture.delete.garment-printing' => ['删除图', 110],
])]
class Picture extends ApiBase
{
    #[Auth([
        'picture.read',
        'common.business.options.picture-list',
        'workbench.quick-query-picture',
        'clothing-style.cost.picture-list',
    ])]
    #[Route('list', 'GET')]
    public function list()
    {
    }

    #[Auth(['picture.action-log-read'])]
    #[Route('action-log$', 'GET')]
    public function actionLog()
    {
    }

    #[Auth(['picture.read', 'workbench.quick-query-picture'])]
    #[Route(':id$', 'GET', pattern: ['id' => '\d+'])]
    public function find(int|null $id)
    {
    }

    #[Auth(['picture.delete.garment-printing'])]
    #[Route(':id$', 'DELETE', pattern: ['id' => '\d+'])]
    public function delete(int $id)
    {
    }

    #[Auth(['picture.read'])]
    #[Route(':id/list-real-photo$', 'GET', pattern: ['id' => '\d+'])]
    public function listOfRealPhoto(int $id)
    {
    }

    #[Auth('picture.real-photo.delete')]
    #[Route(':pictureId/real-photo/:realPhotoId$', 'DELETE', pattern: ['pictureId' => '\d+', 'realPhotoId' => '\d+'])]
    public function deleteRealPhoto(int $pictureId, int $realPhotoId)
    {
    }

    #[Auth(['picture.read'])]
    #[Route(':id/list-clothing-style', 'GET', pattern: ['id' => '\d+'])]
    public function listOfClothingStyle(int $id)
    {
    }

    #[Auth('picture.upload')]
    #[Route('upload', 'POST')]
    public function upload()
    {
    }

    #[Auth('picture.upload')]
    #[Auth('picture.upload-real-photo')]
    #[Route('pre-check', 'POST')]
    public function preCheck()
    {
    }

    #[Auth([
        'picture.upload',
        'picture.real-photo.upload',
        'api.client.picture.real-photo-upload',
    ])]
    #[Route('real-photo-upload-prepare', 'POST')]
    public function realPhotoUploadPrepare()
    {
    }

    #[Auth([
        'picture.upload',
        'picture.real-photo.upload',
        'api.client.picture.real-photo-upload',
    ])]
    #[Route('real-photo-upload-write', 'POST')]
    public function realPhotoUploadWrite()
    {
    }

    #[Auth('picture.upload')]
    #[Auth('picture.upload-real-photo')]
    #[Route('upload-chunk', 'POST')]
    public function chunkUpload()
    {
    }

    #[Auth('picture.edit.meta')]
    #[Auth('picture.edit.print-fabric')]
    #[Route(':id$', 'PUT', pattern: ['id' => '\d+'])]
    public function update(int $id)
    {
    }

    #[Auth('picture.edit.print-fabric')]
    #[Route('batch-print-fabric', 'POST')]
    public function printFabric()
    {
    }

    #[Auth('picture.edit.print-test-result')]
    #[Route('batch-print-test-result', 'POST')]
    public function savePrintTestResult()
    {
    }

    #[Auth('picture.edit.change-owner')]
    #[Route('batch-change-owner', 'POST')]
    public function batchChangeOwner()
    {
    }

    #[Auth('picture.edit.set-listing-status')]
    #[Route('batch-set-listing-status', 'POST')]
    public function batchSetListingStatus()
    {
    }

    #[Auth([
        'picture.read',
        'common.business.options.picture-list',
    ])]
    #[Route('list-listing-date-group-options', 'GET')]
    public function listListingDateGroupOptions()
    {
    }

    #[Auth('picture.read')]
    #[Route('list-product-preview', 'GET')]
    public function listProductPreview()
    {
    }

    #[Auth('picture.edit.filename')]
    #[Route(':id/filename$', 'PUT', pattern: ['id' => '\d+'])]
    public function modifyFilename()
    {
    }
}
