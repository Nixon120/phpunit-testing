<?php

namespace Controllers\Sftp;

use Controllers\AbstractOutputNormalizer;

class OutputNormalizer extends AbstractOutputNormalizer
{
    public function getList(): array
    {
        $list = parent::get();

        return $this->scrubList($list, ['key']);
    }

    public function get(): array {
        $output = parent::get();

        return $this->scrub((array)$output, ['key']);
    }
}
