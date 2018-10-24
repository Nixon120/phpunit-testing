<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class Faqs extends Base
{
    use TimestampTrait;

    public $question;
    public $answer;
    public $program_id;

    /**
     * @return mixed
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param mixed $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return mixed
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param mixed $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return mixed
     */
    public function getProgramId()
    {
        return $this->program_id;
    }

    /**
     * @param mixed $program_id
     */
    public function setProgramId($program_id)
    {
        $this->program_id = $program_id;
    }
}
