<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\Helper\Traits\CreateTrait;

/**
 * Class SearchDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static SearchDataObject create($attributes = null)
 */
class SearchDataObject
{

    use CreateTrait;

    const AND_OPERATOR = 1;
    const OR_OPERATOR = 2;

    public $searchField, $searchPhrase, $operator;

    public function make()
    {
        return [$this->searchField => $this->solveSearchPhrase($this->searchPhrase)];
    }

    private function solveSearchPhrase($phrase)
    {
        if (! is_array($phrase)) {
            return $phrase;
        }

        switch ($this->operator) {
            case self::AND_OPERATOR:
                return implode(" ", $phrase);
            case self::OR_OPERATOR:
            default:
                return implode("+", $phrase);
        }
    }
}
