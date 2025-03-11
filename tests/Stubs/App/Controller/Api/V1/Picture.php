<?php

declare(strict_types=1);

namespace Tests\Stubs\App\Controller\Api\V1;

use Tests\Stubs\App\Controller\ApiBase;
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
