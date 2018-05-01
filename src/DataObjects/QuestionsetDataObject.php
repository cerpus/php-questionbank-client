<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\QuestionBankClient\Traits\CreateTrait;
use Cerpus\QuestionBankClient\Traits\MetadataTrait;
use Illuminate\Support\Collection;

class QuestionsetDataObject extends BaseDataObject
{
    use CreateTrait, MetadataTrait;

    public $title, $id;

    private $questions;

    public $guarded = ['metadata', 'questions'];

    public function __construct()
    {
        $this->questions = collect();
    }

    public function addQuestion(QuestionDataObject $question)
    {
        $this->questions->push($question);
    }

    public function getQuestions()
    {
        return $this->questions;
    }

    public function addQuestions(Collection $questions)
    {
        $questions->each(function ($question) {
            $this->addQuestion($question);
        });
    }

    public function addMetadata(MetadataDataObject $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

}