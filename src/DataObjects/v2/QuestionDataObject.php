<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\Helper\Traits\CreateTrait;
use Illuminate\Support\Collection;

/**
 * Class QuestionDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static QuestionDataObject create($attributes = null)
 */
class QuestionDataObject extends BaseDataObject
{
    use CreateTrait;

    public $text, $questionSetId, $id, $ownerId;
    public $stripMathContainerElements = true;

    private $answers;

    public $guarded = ['answers'];

    public function __construct()
    {
        $this->answers = collect();
    }

    /**
     * @return Collection
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

    public function addOwnerId(string $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'owner_id' => $this->owner_id,
            'answers' => $this->answers->toArray(),
        ];
    }

}
