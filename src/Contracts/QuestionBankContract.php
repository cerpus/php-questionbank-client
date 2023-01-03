<?php

namespace Cerpus\QuestionBankClient\Contracts;

use Cerpus\QuestionBankClient\DataObjects\AnswerDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionsetDataObject;
use Illuminate\Support\Collection;

/**
 * Interface QuestionBankContract
 * @package Cerpus\QuestionBankClient\Contracts
 */
interface QuestionBankContract
{

    public function getQuestionsets($searchParams = null) :Collection;

    public function getQuestionset($questionsetId) :QuestionsetDataObject;

    public function storeQuestionset(QuestionsetDataObject $questionset) :QuestionsetDataObject;

    public function deleteQuestionset($id);

    public function getQuestions($questionsetId) :Collection;

    public function getQuestion($questionId) :QuestionDataObject;

    public function storeQuestion(QuestionDataObject $question) :QuestionDataObject;

    public function deleteQuestion($questionId);

    public function getAnswer($answerId) :AnswerDataObject;

    public function getAnswersByQuestion($questionId) :Collection;

    public function storeAnswer(AnswerDataObject $answer) :AnswerDataObject;

    public function deleteAnswer($answerId);

    public function searchQuestions($searchParams) :Collection;

    public function searchAnswers($searchParams) :Collection;

    public function stripMathContainer($haystack) :string;

    public function storeQuestionSetV2(\stdClass $questionSet) :\stdClass;

    public function searchV2(string $search, array $keywords, bool $includeQuestions, bool $includeAnswers) :Collection;
}
