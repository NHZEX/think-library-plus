<?php

declare(strict_types=1);

namespace Tests\Stubs\app\Controller\Api\V1;

use Tests\Stubs\app\Controller\ApiBase;
use Zxin\Think\Route\Annotation\Group;
use Zxin\Think\Route\Annotation\Route;

#[Group('open-api/v1')]
class Picture extends ApiBase
{
    #[Route('copy-writing-fetch-job', 'POST')]
    public function aiCopyWritingFetchJob()
    {
    }

    #[Route('copy-writing-job-write', 'POST')]
    public function aiCopyWritingJobWrite()
    {
    }
}
