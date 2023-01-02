<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\Helper\Traits\CreateTrait;
use Cerpus\QuestionBankClient\Traits\MetadataTrait;
use Illuminate\Support\Collection;

/**
 * Class QuestionsetsDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static QuestionsetsDataObject create($attributes = null)
 */
class QuestionsetsDataObject extends BaseDataObject
{
    use CreateTrait, MetadataTrait;

    public $id;

    private $questionsets, $ownerId;

    public $guarded = ['questionsets', 'metadata'];

    public function __construct()
    {
        $this->questionsets = collect();
    }

    public function addQuestionset(QuestionsetDataObject $questionset)
    {
        $this->questionsets->push($questionset);
    }

    public function addQuestionsets(Collection $questionsets)
    {
        $questionsets->each(function ($questionset) {
            $this->addQuestionset($questionset);
        });
    }

    public function getQuestionsets()
    {
        return $this->questionsets;
    }
    public function addOwnerId(string $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

}
