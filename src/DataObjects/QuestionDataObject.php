<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\QuestionBankClient\Traits\CreateTrait;
use Cerpus\QuestionBankClient\Traits\MetadataTrait;
use Illuminate\Support\Collection;

class QuestionDataObject extends BaseDataObject
{
    use CreateTrait, MetadataTrait;

    public $text, $questionSetId, $id;

    private $answers;

    public $guarded = ['answers', 'metadata'];

    public function __construct()
    {
        $this->answers = collect();
    }

    /**
     * @return array
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    public function addAnswer(AnswerDataObject $answer)
    {
        $this->answers->push($answer);
    }

    public function addAnswers(Collection $answers)
    {
        $answers->each(function ($answer) {
            $this->addAnswer($answer);
        });
    }
}