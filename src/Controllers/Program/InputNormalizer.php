<?php
namespace Controllers\Program;

use Controllers\AbstractInputNormalizer;

class InputNormalizer extends AbstractInputNormalizer
{
    public function getInput(): array
    {
        $input = parent::getInput();
        if (!empty($input['url'])) {
            $input['url'] = $input['url'] . '.' . $input['domain'];
        }
        unset($input['domain']);
        return $input;
    }
}
