<?php
declare(strict_types=1);

namespace Tests\Stubs\app\Controller;

abstract class ApiResourceBase extends ApiBase
{
    abstract public function index(int $current = 1, int $size = 1);

    abstract public function read();

    abstract public function save();

    abstract public function update();
    abstract public function delete();
}
