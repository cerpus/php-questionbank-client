<?php

namespace Cerpus\QuestionBankClient\DataObjects\v2;


use Cerpus\QuestionBankClient\DataObjects\QuestionDataObject;
use Cerpus\QuestionBankClient\Traits\MetadataTrait;
use Illuminate\Support\Collection;

/**
 * Class QuestionsetDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static QuestionsetDataObject create($attributes = null)
 */
class QuestionsetDataObject extends BaseDataObject
{
    use MetadataTrait;

    public ?string $title = null;
    public ?string $id = null;
    public ?string $ownerId = null;

    private Collection $questions;
    private int $questionCount = 0;

    public $guarded = ['metadata', 'questions'];

    public function __construct()
    {
        $this->questions = collect();
    }

    /**
     * @param  QuestionDataObject  $question
     * @return void
     */
    public function addQuestion(QuestionDataObject $question) :void
    {
        $this->questions->push($question);
    }

    /**
     * @return Collection
     */
    public function getQuestions() :Collection
    {
        return $this->questions;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'owner_id' => $this->owner_id,
            'questions' => $this->questions->toArray(),
        ];
    }
}
