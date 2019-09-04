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
}
