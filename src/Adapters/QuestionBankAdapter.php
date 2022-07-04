<?php

namespace Cerpus\QuestionBankClient\Adapters;

use Cerpus\QuestionBankClient\Contracts\QuestionBankContract;
use Cerpus\QuestionBankClient\DataObjects\AnswerDataObject;
use Cerpus\QuestionBankClient\DataObjects\MetadataDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionsetDataObject;
use Cerpus\QuestionBankClient\DataObjects\SearchDataObject;
use Cerpus\QuestionBankClient\Exceptions\InvalidSearchParametersException;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;
use Log;

/**
 * Class QuestionBankAdapter
 * @package Cerpus\QuestionBankClient\Adapters
 */
class QuestionBankAdapter implements QuestionBankContract
{
    /** @var ClientInterface */
    private $client;

    /** @var Exception */
    private $error;

    const QUESTIONSETS = '/v1/question_sets';
    const QUESTIONSET = '/v1/question_sets/%s';

    const QUESTIONSET_QUESTIONS = '/v1/question_sets/%s/questions';
    const QUESTIONS = '/v1/questions';
    const QUESTION = '/v1/questions/%s';

    const QUESTION_ANSWERS = '/v1/questions/%s/answers';
    const ANSWERS = '/v1/answers';
    const ANSWER = '/v1/answers/%s';

    /**
     * QuestionBankAdapter constructor.
     * @param  ClientInterface  $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param  object  $metadata
     * @return MetadataDataObject
     */
    private function transformMetadata($metadata)
    {
        return MetadataDataObject::create([
            'keywords' => $metadata->keywords,
            'images' => $metadata->images,
        ]);
    }

    /**
     * @param  object  $questionValues
     * @return QuestionsetDataObject
     */
    private function mapQuestionsetResponseToDataObject($questionValues)
    {
        $questionset = QuestionsetDataObject::create([
            'id' => $questionValues->id,
            'title' => $questionValues->title,
            'questionCount' => property_exists($questionValues, 'questionCount') ? (int) $questionValues->questionCount : null,
            'owner_id' => $questionValues->ownerId
        ]);
        $questionset->addMetadata($this->transformMetadata($questionValues->metadata));
        return $questionset;
    }

    /**
     * @param  object  $questionValues
     * @return QuestionDataObject
     */
    private function mapQuestionResponseToDataObject($questionValues)
    {
        $question = QuestionDataObject::create([
            'id' => $questionValues->id,
            'text' => $questionValues->title,
            'questionSetId' => $questionValues->questionSetId,
            'owner_id' => $questionValues->ownerId
        ]);
        $question->addMetadata($this->transformMetadata($questionValues->metadata));
        return $question;
    }

    /**
     * @param  object  $answerValues
     * @return AnswerDataObject
     */
    private function mapAnswerResponseToDataObject($answerValues) :AnswerDataObject
    {
        $answer = AnswerDataObject::create([
            'id' => $answerValues->id,
            'text' => $answerValues->description,
            'questionId' => $answerValues->questionId,
            'isCorrect' => intval($answerValues->correctness) === 100,
        ]);
        $answer->addMetadata($this->transformMetadata($answerValues->metadata));
        return $answer;
    }

    /**
     * @param  Collection|SearchDataObject  $search
     * @return array
     * @throws InvalidSearchParametersException
     */
    private function traverseSearch($search) :array
    {
        if (! is_object($search) || ! in_array(get_class($search), [
                Collection::class,
                SearchDataObject::class,
            ])) {
            throw new InvalidSearchParametersException();
        }

        if (is_a($search, SearchDataObject::class)) {
            $params = collect([$search]);
        } else {
            $params = $search;
        }

        $queryParams = $params
            ->map(function (SearchDataObject $param) {
                return $param->make();
            })
            ->reduce(function ($old, $new) {
                return array_merge($old, $new);
            }, []);
        return ['query' => $queryParams];
    }

    /**
     * @param  Collection|SearchDataObject  $search  = null
     * @param  boolean  $includeQuestions  = true
     * @return Collection[QuestionsetDataObject]
     * @throws InvalidSearchParametersException
     * @throws GuzzleException
     */
    public function getQuestionsets($search = null, $includeQuestions = true) :Collection
    {
        $additionalParameters = ! is_null($search) ? $this->traverseSearch($search) : [];
        $response = $this->client->request("GET", self::QUESTIONSETS, $additionalParameters);
        $data = collect(\GuzzleHttp\json_decode($response->getBody()));
        $questionsets = $data->map(function ($questionset) {
            return $this->mapQuestionsetResponseToDataObject($questionset);
        });
        if ($includeQuestions === true) {
            $questionsets->each(function ($questionset) {
                /** @var QuestionsetDataObject $questionset */
                $questionset->addQuestions($this->getQuestions($questionset->id));
            });
        }
        return $questionsets;
    }

    /**
     * @param  string  $questionsetId
     * @param  bool  $includeQuestions
     * @return QuestionsetDataObject
     * @throws GuzzleException
     */
    public function getQuestionset($questionsetId, $includeQuestions = true) :QuestionsetDataObject
    {
        $response = $this->client->request("GET", sprintf(self::QUESTIONSET, $questionsetId));
        $data = \GuzzleHttp\json_decode($response->getBody());
        $questionset = $this->mapQuestionsetResponseToDataObject($data);
        if ($includeQuestions === true) {
            /** @var QuestionsetDataObject $questionset */
            $questionset->addQuestions($this->getQuestions($questionset->id));
        }
        return $questionset;
    }

    /**
     * @param  QuestionsetDataObject  $questionset
     * @return QuestionsetDataObject
     * @throws GuzzleException
     */
    public function storeQuestionset(QuestionsetDataObject $questionset) :QuestionsetDataObject
    {
        if (empty($questionset->id)) {
            return $this->createQuestionset($questionset);
        } else {
            return $this->updateQuestionset($questionset);
        }
    }

    /**
     * @param  QuestionsetDataObject  $questionset
     * @return QuestionsetDataObject
     * @throws GuzzleException
     */
    private function createQuestionset(QuestionsetDataObject $questionset) :QuestionsetDataObject
    {
        if (is_null($questionset->getMetadata())) {
            $questionset->addMetadata(MetadataDataObject::create());
        }
        $questionsetStructure = (object) [
            'title' => $questionset->title,
            'metadata' => $questionset->getMetadata(),
            'owner_id' => $questionset->ownerId
        ];

        $response = $this->client->request("POST", self::QUESTIONSETS, ['json' => $questionsetStructure]);
        $questionsetResponse = \GuzzleHttp\json_decode($response->getBody());
        $createdQuestionset = $this->mapQuestionsetResponseToDataObject($questionsetResponse);
        $createdQuestionset->wasRecentlyCreated = true;
        return $createdQuestionset;
    }

    /**
     * @param  QuestionsetDataObject  $questionset
     * @return QuestionsetDataObject
     * @throws GuzzleException
     */
    private function updateQuestionset(QuestionsetDataObject $questionset) :QuestionsetDataObject
    {
        if (is_null($questionset->getMetadata())) {
            $questionset->addMetadata(MetadataDataObject::create());
        }
        $questionsetStructure = (object) [
            'title' => $questionset->title,
            'metadata' => $questionset->getMetadata(),
            'owner_id' => $questionset->ownerId
        ];

        $response = $this->client->request("PUT", sprintf(self::QUESTIONSET, $questionset->id), ['json' => $questionsetStructure]);
        $questionsetResponse = \GuzzleHttp\json_decode($response->getBody());
        return $this->mapQuestionsetResponseToDataObject($questionsetResponse);
    }

    /**
     * @param $id
     */
    public function deleteQuestionset($id)
    {
        // TODO: Implement deleteQuestionset() method.
    }

    /**
     * @param $questionsetId
     * @param  boolean  $concurrent
     * @return Collection[QuestionDataObject]
     * @throws GuzzleException
     */
    public function getQuestions($questionsetId, $concurrent = false) :Collection
    {
        $response = $this->client->request("GET", sprintf(self::QUESTIONSET_QUESTIONS, $questionsetId));
        $data = collect(\GuzzleHttp\json_decode($response->getBody()));

        $questions = $data->map(function ($question) use ($concurrent) {
            $question = $this->mapQuestionResponseToDataObject($question);
            if ($concurrent === false) {
                $question->addAnswers($this->getAnswersByQuestion($question->id));
            }

            return $question;
        });

        $questionsAndAnswers = collect();
        if ($concurrent) {
            $questionsAndAnswers = $this->asyncAddAnswers($questions);
        } else {
            $questionsAndAnswers = $questions;
        }

        return $questionsAndAnswers;
    }

    /**
     * @param  Collection  $questions
     * @return Collection
     */
    protected function asyncAddAnswers(Collection $questions) :Collection
    {
        try {
            $client = $this->client;

            $requests = $questions->mapWithKeys(function ($q) use ($client) {
                return [$q->id => new Request('GET', sprintf(self::QUESTION_ANSWERS, $q->id))];
            })->toArray();

            $responses = Pool::batch($this->client, $requests, ['concurrency' => 50]);

            $answers = collect($responses)->mapWithKeys(function ($response, $key) {
                $a = json_decode($response->getBody());
                $answers = collect($a)
                    ->map(function ($a) {
                        $asdo = $this->mapAnswerResponseToDataObject($a);
                        return $asdo;
                    });

                return [$key => $answers];
            });

            $questions->each(function (QuestionDataObject $q) use ($answers) {
                $q->addAnswers($answers[$q->id]);

                return $q;
            });
        } catch (Exception $e) {
            Log::error(__METHOD__.': QuestionBankAdapter error: '.$e->getMessage());
        }

        return $questions;
    }

    /**
     * @param $questionId
     * @param  bool  $includeAnswers
     * @return QuestionDataObject
     * @throws GuzzleException
     */
    public function getQuestion($questionId, $includeAnswers = true) :QuestionDataObject
    {
        $response = $this->client->request("GET", sprintf(self::QUESTION, $questionId));
        $data = \GuzzleHttp\json_decode($response->getBody());
        $question = $this->mapQuestionResponseToDataObject($data);
        if ($includeAnswers === true) {
            $question->addAnswers($this->getAnswersByQuestion($question->id));
        }
        return $question;
    }

    /**
     * @param  QuestionDataObject  $question
     * @return QuestionDataObject
     * @throws GuzzleException
     */
    public function storeQuestion(QuestionDataObject $question) :QuestionDataObject
    {
        if (empty($question->id)) {
            return $this->createQuestion($question);
        } else {
            return $this->updateQuestion($question);
        }
    }

    /**
     * @param  QuestionDataObject  $question
     * @return QuestionDataObject
     * @throws GuzzleException
     */
    private function createQuestion(QuestionDataObject $question) :QuestionDataObject
    {
        if (is_null($question->getMetadata())) {
            $question->addMetadata(MetadataDataObject::create());
        }

        $questionText = $question->stripMathContainerElements === true ? $this->stripMathContainer($question->text) : $question->text;
        $questionStructure = (object) [
            'title' => $questionText,
            'metadata' => $question->getMetadata(),
            'owner_id' => $question->ownerId
        ];

        $response = $this->client->request("POST", sprintf(self::QUESTIONSET_QUESTIONS, $question->questionSetId), ['json' => $questionStructure]);
        $questionResponse = \GuzzleHttp\json_decode($response->getBody());
        $createdQuestion = $this->mapQuestionResponseToDataObject($questionResponse);
        $createdQuestion->wasRecentlyCreated = true;
        return $createdQuestion;
    }

    /**
     * @param  QuestionDataObject  $question
     * @return QuestionDataObject
     * @throws GuzzleException
     */
    private function updateQuestion(QuestionDataObject $question) :QuestionDataObject
    {
        if (is_null($question->getMetadata())) {
            $question->addMetadata(MetadataDataObject::create());
        }

        $questionText = $question->stripMathContainerElements === true ? $this->stripMathContainer($question->text) : $question->text;
        $questionStructure = (object) [
            'title' => $questionText,
            'metadata' => $question->getMetadata(),
            'owner_id' => $question->ownerId
        ];

        $response = $this->client->request("PUT", sprintf(self::QUESTION, $question->id), ['json' => $questionStructure]);
        $questionResponse = \GuzzleHttp\json_decode($response->getBody());
        return $this->mapQuestionResponseToDataObject($questionResponse);
    }

    /**
     * @param $questionId
     */
    public function deleteQuestion($questionId)
    {
        // TODO: Implement deleteQuestion() method.
    }

    /**
     * @param $answerId
     * @return AnswerDataObject
     * @throws GuzzleException
     */
    public function getAnswer($answerId) :AnswerDataObject
    {
        $response = $this->client->request("GET", sprintf(self::ANSWER, $answerId));
        $data = \GuzzleHttp\json_decode($response->getBody());
        return $this->mapAnswerResponseToDataObject($data);
    }

    /**
     * @param $questionId
     * @return Collection[AnswerDataObject]
     * @throws GuzzleException
     */
    public function getAnswersByQuestion($questionId) :Collection
    {
        $response = $this->client->request("GET", sprintf(self::QUESTION_ANSWERS, $questionId));
        $data = collect(\GuzzleHttp\json_decode($response->getBody()));
        return $data->map(function ($answer) {
            return $this->mapAnswerResponseToDataObject($answer);
        });
    }

    /**
     * @param  AnswerDataObject  $answer
     * @return AnswerDataObject
     * @throws GuzzleException
     */
    public function storeAnswer(AnswerDataObject $answer) :AnswerDataObject
    {
        if (empty($answer->id)) {
            return $this->createAnswer($answer);
        } else {
            return $this->updateAnswer($answer);
        }
    }

    /**
     * @param  AnswerDataObject  $answer
     * @return AnswerDataObject
     * @throws GuzzleException
     */
    private function createAnswer(AnswerDataObject $answer) :AnswerDataObject
    {
        if (is_null($answer->getMetadata())) {
            $answer->addMetadata(MetadataDataObject::create());
        }

        $answerText = $answer->stripMathContainerElements === true ? $this->stripMathContainer($answer->text) : $answer->text;
        $answerStructure = (object) [
            'description' => $answerText,
            'correctness' => ! empty($answer->isCorrect) ? 100 : 0,
            'metadata' => $answer->getMetadata(),
        ];

        $response = $this->client->request("POST", sprintf(self::QUESTION_ANSWERS, $answer->questionId), ['json' => $answerStructure]);
        $answerResponse = \GuzzleHttp\json_decode($response->getBody());
        $createdAnswer = $this->mapAnswerResponseToDataObject($answerResponse);
        $createdAnswer->wasRecentlyCreated = true;
        return $createdAnswer;
    }

    /**
     * @param  AnswerDataObject  $answer
     * @return AnswerDataObject
     * @throws GuzzleException
     */
    private function updateAnswer(AnswerDataObject $answer) :AnswerDataObject
    {
        if (is_null($answer->getMetadata())) {
            $answer->addMetadata(MetadataDataObject::create());
        }

        $answerText = $answer->stripMathContainerElements === true ? $this->stripMathContainer($answer->text) : $answer->text;
        $answerStructure = (object) [
            'description' => $answerText,
            'correctness' => ! empty($answer->isCorrect) ? 100 : 0,
            'metadata' => $answer->getMetadata(),
        ];

        $response = $this->client->request("PUT", sprintf(self::ANSWER, $answer->id), ['json' => $answerStructure]);
        $answerResponse = \GuzzleHttp\json_decode($response->getBody());
        return $this->mapAnswerResponseToDataObject($answerResponse);
    }

    /**
     * @param $answerId
     */
    public function deleteAnswer($answerId)
    {
        // TODO: Implement deleteAnswer() method.
    }


    /**
     * @param  Collection|null  $searchParams
     * @return Collection
     * @throws InvalidSearchParametersException
     * @throws GuzzleException
     */
    public function searchQuestions($searchParams) :Collection
    {
        $additionalParameters = ! is_null($searchParams) ? $this->traverseSearch($searchParams) : [];
        $response = $this->client->request("GET", self::QUESTIONS, $additionalParameters);
        $data = collect(\GuzzleHttp\json_decode($response->getBody()));
        $questions = $data->map(function ($question) {
            return $this->mapQuestionResponseToDataObject($question);
        })
            ->map(function (QuestionDataObject $question) {
                $answers = $this->getAnswersByQuestion($question->id);
                $question->addAnswers($answers);
                return $question;
            });
        return $questions;
    }

    /**
     * @param  null  $searchParams
     * @return Collection
     * @throws InvalidSearchParametersException
     * @throws GuzzleException
     */
    public function searchAnswers($searchParams) :Collection
    {
        $additionalParameters = ! is_null($searchParams) ? $this->traverseSearch($searchParams) : [];
        $response = $this->client->request("GET", self::ANSWERS, $additionalParameters);
        $data = collect(\GuzzleHttp\json_decode($response->getBody()));
        $answers = $data->map(function ($answer) {
            return $this->mapAnswerResponseToDataObject($answer);
        });
        return $answers;
    }

    public function stripMathContainer($text) :string
    {
        $pattern = [
            '/<span.+?class=.math_container.*?>([\s\S]+?)<\/span>/i',
            '/\${2}\\\\\(([\s\S]+?)\\\\\)\${2}/i',
        ];
        $replace = '\\$\\$$1\\$\\$';
        return preg_replace($pattern, $replace, $text);
    }

    public function convertMathToInlineDisplay($text)
    {
        $pattern = [
            '/\$\$(.+?)\$\$/i',
        ];
        $replace = '\\\\\\\\( $1 \\\\\\\\)';
        return preg_replace($pattern, $replace, $text);
    }
}
