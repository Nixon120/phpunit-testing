<?php
namespace Controllers\User;

use Controllers\AbstractInputNormalizer;

class InputNormalizer extends AbstractInputNormalizer
{
    public function setRole($role)
    {
        $input = $this->getInput();
        $input['role'] = $role;
        $this->setInput($input);
    }
}
