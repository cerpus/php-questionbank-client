<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\Helper\Traits\CreateTrait;

/**
 * Class AnswerDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static AnswerDataObject create($attributes = null)
 */
class AnswerDataObject
{
    use CreateTrait;

    public $text, $id, $isCorrect, $questionId;
    public $stripMathContainerElements = true;
}
