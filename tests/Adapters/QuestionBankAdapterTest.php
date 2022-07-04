<?php

namespace Cerpus\QuestionBankClientTests\Adapters;

use Cerpus\QuestionBankClient\Adapters\QuestionBankAdapter;
use Cerpus\QuestionBankClient\DataObjects\AnswerDataObject;
use Cerpus\QuestionBankClient\DataObjects\MetadataDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionDataObject;
use Cerpus\QuestionBankClient\DataObjects\QuestionsetDataObject;
use Cerpus\QuestionBankClient\DataObjects\SearchDataObject;
use Cerpus\QuestionBankClientTests\Utils\QuestionBankTestCase;
use Cerpus\QuestionBankClientTests\Utils\Traits\WithFaker;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Teapot\StatusCode;

class QuestionBankAdapterTest extends QuestionBankTestCase
{
    use WithFaker;

    private function getClient(array $responses)
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }

    /**
     * @test
     */
    public function getQuestionsets_empty_thenSuccess()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [], "[]"),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $questionsets = $adapter->getQuestionsets(null, false);
        $this->assertEquals(get_class($questionsets), Collection::class);
        $this->assertCount(0, $questionsets);
    }

    /**
     * @test
     */
    public function getQuestionsets_twoSets_thenSuccess()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '[{"metadata": {"keywords": ["progress"],"images": []},"id": "c2197f7c-668d-4464-b645-cb4068f7eade","title": "QS progress","ownerId": "' . $ownerId . '"},{"metadata": {"keywords": ["test"],"images": []},"id": "dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title": "QS inmaking","ownerId": "' . $ownerId . '"}]'),
        ]);

        $adapter = $this->getMockBuilder(QuestionBankAdapter::class)
            ->setConstructorArgs(['client' => $client])
            ->setMethods(['getQuestions'])
            ->getMock();
        $adapter->method('getQuestions')->willReturn(collect([QuestionDataObject::create()]));

        /** @var QuestionBankAdapter $adapter */
        $questionsets = $adapter->getQuestionsets(null, true);
        $this->assertEquals(get_class($questionsets), Collection::class);
        $questionset = $questionsets->random();
        $this->assertCount(2, $questionsets);
        $this->assertEquals(get_class($questionset), QuestionsetDataObject::class);
    }

    /**
     * @test
     */
    public function getQuestionsetWithoutQuestions()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata": {"keywords": ["progress"],"images": []},"id": "c2197f7c-668d-4464-b645-cb4068f7eade","title": "QS progress","ownerId":"' . $ownerId . '"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $questionset = $adapter->getQuestionset('c2197f7c-668d-4464-b645-cb4068f7eade', false);
        $this->assertEquals('c2197f7c-668d-4464-b645-cb4068f7eade', $questionset->id);
        $this->assertEmpty($questionset->getQuestions());
        $this->assertEquals(0, $questionset->questionCount);
    }

    /**
     * @test
     */
    public function getQuestionsetWithQuestions()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"questionCount": 1, "metadata": {"keywords": ["progress"],"images": []},"id": "c2197f7c-668d-4464-b645-cb4068f7eade","title": "QS progress","ownerId":"' . $ownerId . '"}'),
        ]);

        $adapter = $this->getMockBuilder(QuestionBankAdapter::class)
            ->setConstructorArgs(['client' => $client])
            ->setMethods(['getQuestions'])
            ->getMock();
        $adapter->method('getQuestions')->willReturn(collect([QuestionDataObject::create()]));

        /** @var QuestionBankAdapter $adapter */
        $questionset = $adapter->getQuestionset('c2197f7c-668d-4464-b645-cb4068f7eade');
        $this->assertEquals('c2197f7c-668d-4464-b645-cb4068f7eade', $questionset->id);
        $this->assertNotEmpty($questionset->getQuestions());
        $this->assertEquals(1, $questionset->questionCount);
    }

    /**
     * @test
     */
    public function getQuestions_empty_thenSuccess()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [], "[]"),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $questions = $adapter->getQuestions("questionsetWithNoQuestions");
        $this->assertEquals(get_class($questions), Collection::class);
        $this->assertCount(0, $questions);
    }

    /**
     * @test
     */
    public function getQuestions_threeQuestions_thenSuccess()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '[{"metadata":{"keywords":[],"images": []},"id":"37a3ebce-002c-4e74-b611-0cb6e2e91515","questionSetId":"dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title":"QS progress 3","ownerId": "' . $ownerId . '"},{"metadata":{"keywords":["progress2"],"images": []},"id":"6bdeda3c-1169-47c5-b173-782e7f36f9fc","questionSetId":"dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title":"QS progress 2","ownerId": "' . $ownerId . '"},{"metadata":{"keywords":["testquestion"],"images": []},"id":"a184acf1-4c78-4f43-9a44-aad294dcc146","questionSetId":"dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title":"QS question","ownerId": "' . $ownerId . '"}]'),
        ]);

        $adapter = $this->getMockBuilder(QuestionBankAdapter::class)
            ->setConstructorArgs(['client' => $client])
            ->setMethods(['getAnswersByQuestion'])
            ->getMock();
        $adapter->method('getAnswersByQuestion')->willReturn(collect([AnswerDataObject::create()]));

        /** @var QuestionBankAdapter $adapter */
        $questions = $adapter->getQuestions("questionsetWithNoQuestions");
        $this->assertEquals(get_class($questions), Collection::class);
        $question = $questions->random();
        $this->assertCount(3, $questions);
        $this->assertEquals(get_class($question), QuestionDataObject::class);
    }

    /**
     * @test
     */
    public function getQuestionWithAnswers()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"37a3ebce-002c-4e74-b611-0cb6e2e91515","questionSetId":"dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title":"Existing question","ownerId":"' . $ownerId . '"}'),
        ]);

        $adapter = $this->getMockBuilder(QuestionBankAdapter::class)
            ->setConstructorArgs(['client' => $client])
            ->setMethods(['getAnswersByQuestion'])
            ->getMock();
        $adapter->method('getAnswersByQuestion')->willReturn(collect([AnswerDataObject::create()]));

        /** @var QuestionBankAdapter $adapter */
        $question = $adapter->getQuestion('37a3ebce-002c-4e74-b611-0cb6e2e91515');
        $this->assertEquals('37a3ebce-002c-4e74-b611-0cb6e2e91515', $question->id);
        $this->assertEquals('Existing question', $question->text);
        $this->assertEquals('dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2', $question->questionSetId);
        $this->assertNotEmpty($question->getAnswers());
    }

    /**
     * @test
     */
    public function getQuestionWithoutAnswers()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"37a3ebce-002c-4e74-b611-0cb6e2e91515","questionSetId":"dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title":"Existing question","ownerId":"' . $ownerId . '"}'),
        ]);

        $adapter = $this->getMockBuilder(QuestionBankAdapter::class)
            ->setConstructorArgs(['client' => $client])
            ->setMethods(['getAnswersByQuestion'])
            ->getMock();
        $adapter->method('getAnswersByQuestion')->willReturn(collect([AnswerDataObject::create()]));

        /** @var QuestionBankAdapter $adapter */
        $question = $adapter->getQuestion('37a3ebce-002c-4e74-b611-0cb6e2e91515');
        $this->assertEquals('37a3ebce-002c-4e74-b611-0cb6e2e91515', $question->id);
        $this->assertEquals('Existing question', $question->text);
        $this->assertEquals('dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2', $question->questionSetId);
    }

    /**
     * @test
     */
    public function getAnswers_empty_thenSuccess()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [], "[]"),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $questions = $adapter->getAnswersByQuestion("QuestionWithNoAnswers");
        $this->assertEquals(get_class($questions), Collection::class);
        $this->assertCount(0, $questions);
    }

    /**
     * @test
     */
    public function getAnswers_twoAnswers_thenSuccess()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '[{"metadata":{"keywords":[],"images": []},"id":"7b937904-adfc-417a-bb7c-b9a7ad576709","questionId":"a184acf1-4c78-4f43-9a44-aad294dcc146","description":"QS answer 2","correctness":0},{"metadata":{"keywords":["testanswer"],"images": []},"id":"cebcf2f0-b233-4615-ac65-70d1af015f9a","questionId":"a184acf1-4c78-4f43-9a44-aad294dcc146","description":"QS answer","correctness":100}]'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $questions = $adapter->getAnswersByQuestion("QuestionWithNoAnswers");
        $this->assertEquals(get_class($questions), Collection::class);
        $this->assertCount(2, $questions);
    }

    /**
     * @test
     */
    public function getAnswer()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","questionId":"a184acf1-4c78-4f43-9a44-aad294dcc146","description":"Existing answer","correctness":0}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $answer = $adapter->getAnswer('cebfd105-d5e5-4158-9568-2f8a1252ccb4');
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $answer->id);
        $this->assertEquals('Existing answer', $answer->text);
        $this->assertEquals('a184acf1-4c78-4f43-9a44-aad294dcc146', $answer->questionId);
    }

    /**
     * @test
     */
    public function createQuestionset()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"New Questionset","ownerId":"' . $ownerId . '"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestionset = $adapter->storeQuestionset(QuestionsetDataObject::create("New Questionset"));
        $this->assertEquals(get_class($savedQuestionset), QuestionsetDataObject::class);
        $this->assertEquals("New Questionset", $savedQuestionset->title);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestionset->id);
        $this->assertTrue($savedQuestionset->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function createQuestion()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"New Question","questionSetId":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","ownerId":"' . $ownerId . '"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestion = $adapter->storeQuestion(QuestionDataObject::create("New Question"));
        $this->assertEquals(get_class($savedQuestion), QuestionDataObject::class);
        $this->assertEquals("New Question", $savedQuestion->text);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestion->id);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedQuestion->questionSetId);
        $this->assertTrue($savedQuestion->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function createQuestionWithMath()
    {
        $questionsetId = $this->faker->uuid;
        $ownerId = $this->faker->uuid;
        $questionText = '<p>Albert Einstein formula: <span class="math_container">\(E=mc^2\)</span></p>';
        $question = QuestionDataObject::create($questionText, $questionsetId);
        $question->ownerId = $ownerId;

        $expectedParams = [
            'json' => (object) [
                'title' => '<p>Albert Einstein formula: $$E=mc^2$$</p>',
                'metadata' => MetadataDataObject::create(),
                'ownerId' => $ownerId,
            ],
        ];

        $client = $this->getMockBuilder(Client::class)
            ->setMethods(['request'])
            ->getMock();


        $client
            ->expects($this->once())
            ->method('request')
            ->with("POST", sprintf(QuestionBankAdapter::QUESTIONSET_QUESTIONS, $questionsetId), $expectedParams)
            ->willReturn(
                new Response(StatusCode::OK, [],
                    '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"<p>Albert Einstein formula: $$E=mc^2$$<\/p>","questionSetId":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","ownerId": "' . $ownerId . '"}')
            );

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestion = $adapter->storeQuestion($question);
        $this->assertEquals(get_class($savedQuestion), QuestionDataObject::class);
        $this->assertEquals('<p>Albert Einstein formula: $$E=mc^2$$</p>', $savedQuestion->text);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestion->id);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedQuestion->questionSetId);
        $this->assertEquals($ownerId, $savedQuestion->ownerId);
        $this->assertTrue($savedQuestion->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function createQuestionWithMathWithoutReplacment()
    {
        $ownerId = $this->faker->uuid;
        $questionsetId = $this->faker->uuid;
        $questionText = '<p>Albert Einstein formula: <span class="math_container">\(E=mc^2\)</span></p>';
        $question = QuestionDataObject::create($questionText, $questionsetId);
        $question->stripMathContainerElements = false;
        $question->ownerId = $ownerId;

        $expectedParams = [
            'json' => (object) [
                'title' => $questionText,
                'metadata' => MetadataDataObject::create(),
                'ownerId' => $ownerId,
            ],
        ];

        $client = $this->getMockBuilder(Client::class)
            ->setMethods(['request'])
            ->getMock();

        $client
            ->expects($this->once())
            ->method('request')
            ->with("POST", sprintf(QuestionBankAdapter::QUESTIONSET_QUESTIONS, $questionsetId), $expectedParams)
            ->willReturn(
                new Response(StatusCode::OK, [],
                    '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"<p>Albert Einstein formula: <span class=\"math_container\">\\\(E=mc^2\\\)<\/span><\/p>","questionSetId":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","ownerId": "' . $ownerId . '"}')
            );

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestion = $adapter->storeQuestion($question);
        $this->assertEquals(get_class($savedQuestion), QuestionDataObject::class);
        $this->assertEquals($questionText, $savedQuestion->text);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestion->id);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedQuestion->questionSetId);
        $this->assertEquals($ownerId, $savedQuestion->ownerId);
        $this->assertTrue($savedQuestion->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function createAnswer()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","questionId":"a184acf1-4c78-4f43-9a44-aad294dcc146","description":"New Answer","correctness":100}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedAnswer = $adapter->storeAnswer(AnswerDataObject::create("New Answer"));
        $this->assertEquals(get_class($savedAnswer), AnswerDataObject::class);
        $this->assertEquals("New Answer", $savedAnswer->text);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedAnswer->id);
        $this->assertTrue($savedAnswer->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function updateQuestionset()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"Updated Questionset","ownerId":"' . $ownerId . '"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestionset = $adapter->storeQuestionset(QuestionsetDataObject::create("New Questionset"));
        $this->assertEquals(get_class($savedQuestionset), QuestionsetDataObject::class);
        $this->assertEquals("Updated Questionset", $savedQuestionset->title);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestionset->id);
    }

    /**
     * @test
     */
    public function updateQuestion()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"d8884054-5fb4-4f4e-9fd6-6bceb85ee57d","title":"Updated Question","questionSetId":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","ownerId":"' . $ownerId . '"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedQuestion = $adapter->storeQuestion(QuestionDataObject::create("New Question"));
        $this->assertEquals(get_class($savedQuestion), QuestionDataObject::class);
        $this->assertEquals("Updated Question", $savedQuestion->text);
        $this->assertEquals('d8884054-5fb4-4f4e-9fd6-6bceb85ee57d', $savedQuestion->id);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedQuestion->questionSetId);
    }

    /**
     * @test
     */
    public function updateAnswer()
    {
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '{"metadata":{"keywords":[],"images": []},"id":"cebfd105-d5e5-4158-9568-2f8a1252ccb4","questionId":"a184acf1-4c78-4f43-9a44-aad294dcc146","description":"Updated Answer","correctness":100}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $savedAnswer = $adapter->storeAnswer(AnswerDataObject::create("New Answer", $this->faker->uuid));
        $this->assertEquals(get_class($savedAnswer), AnswerDataObject::class);
        $this->assertEquals("Updated Answer", $savedAnswer->text);
        $this->assertEquals('cebfd105-d5e5-4158-9568-2f8a1252ccb4', $savedAnswer->id);
        $this->assertFalse($savedAnswer->wasRecentlyCreated);
    }

    /**
     * @test
     */
    public function getQuestionsetsWithSearch()
    {
        $ownerId = $this->faker->uuid;
        /** @var ClientInterface $client */
        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::QUESTIONSETS, ['query' => ['search' => 'Nytt']])
            ->willReturn(new Response(StatusCode::OK, [],
                '[{"metadata":{"keywords":[],"images": []},"id":"cda71174-2d82-439a-bd4a-343e982cdaa9","title":"Nytt på nytt","ownerId": "' . $ownerId . '"},{"metadata":{"keywords":[],"images": []},"id":"b9da2705-4e8f-4d43-bb28-93bb3ba9d215","title":"Helt nytt","ownerId":"' . $ownerId . '"}]'));

        $search = SearchDataObject::create('search', 'Nytt');
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $questionsets = $adapter->getQuestionsets($search, false);
        $this->assertEquals(get_class($questionsets), Collection::class);
        $questionset = $questionsets->random();
        $this->assertCount(2, $questionsets);
        $this->assertEquals(get_class($questionset), QuestionsetDataObject::class);

        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::QUESTIONSETS, ['query' => ['search' => 'Nytt på']])
            ->willReturn(new Response(StatusCode::OK, [],
                '[{"metadata":{"keywords":[],"images": []},"id":"cda71174-2d82-439a-bd4a-343e982cdaa9","title":"Nytt på nytt","ownerId":"' . $ownerId . '"}]'));

        $search = SearchDataObject::create('search', 'Nytt på');
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $questionsets = $adapter->getQuestionsets($search, false);
        $this->assertEquals(get_class($questionsets), Collection::class);
        $questionset = $questionsets->random();
        $this->assertCount(1, $questionsets);
        $this->assertEquals(get_class($questionset), QuestionsetDataObject::class);

        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::QUESTIONSETS, [
                'query' => [
                    'search' => 'Nytt',
                    'keyword' => 'fjell',
                ],
            ])
            ->willReturn(new Response(StatusCode::OK, [], '[]'));

        $search = collect([
            SearchDataObject::create('search', 'Nytt'),
            SearchDataObject::create('keyword', 'fjell'),
        ]);
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $adapter->getQuestionsets($search, false);

        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::QUESTIONSETS, [
                'query' => [
                    'search' => 'Nytt',
                    'keyword' => 'keyword1+keyword2',
                ],
            ])
            ->willReturn(new Response(StatusCode::OK, [], '[]'));

        $search = collect([
            SearchDataObject::create('search', 'Nytt'),
            SearchDataObject::create('keyword', ['keyword1', 'keyword2']),
        ]);
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $adapter->getQuestionsets($search, false);

        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::QUESTIONSETS, [
                'query' => [
                    'search' => 'Nytt',
                    'keyword' => 'keyword1 keyword2',
                ],
            ])
            ->willReturn(new Response(StatusCode::OK, [], '[]'));

        $search = collect([
            SearchDataObject::create('search', 'Nytt'),
            SearchDataObject::create('keyword', ['keyword1', 'keyword2'], SearchDataObject::AND_OPERATOR),
        ]);
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $adapter->getQuestionsets($search, false);
    }

    /**
     * @test
     */
    public function getQuestionsWithSearch()
    {
        $ownerId = $this->faker->uuid;
        $client = $this->getClient([
            new Response(StatusCode::OK, [],
                '[{"metadata": {"keywords": [],"images": []},"id": "6bdeda3c-1169-47c5-b173-782e7f36f9fc","questionSetId": "dd4c2d2f-6490-4611-9be8-df5d1c7c6eb2","title": "Updated question","ownerId":"' . $ownerId . '"}]'),
            new Response(StatusCode::OK, [],
                '[{"metadata":{"keywords":[],"images": []},"id":"7b937904-adfc-417a-bb7c-b9a7ad576709","questionId":"6bdeda3c-1169-47c5-b173-782e7f36f9fc","description":"Answer 1","correctness":0},{"metadata":{"keywords":["testanswer"],"images": []},"id":"cebcf2f0-b233-4615-ac65-70d1af015f9a","questionId":"6bdeda3c-1169-47c5-b173-782e7f36f9fc","description":"Answer 2","correctness":100}]'),
            new Response(StatusCode::OK, [], '[]'),
        ]);

        $search = SearchDataObject::create('search', 'Question');
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $questions = $adapter->searchQuestions($search);
        $this->assertEquals(get_class($questions), Collection::class);
        $question = $questions->first();
        $this->assertCount(1, $questions);
        $this->assertEquals(get_class($question), QuestionDataObject::class);
        $this->assertEquals("Updated question", $question->text);
        $this->assertCount(2, $question->getAnswers());

        $search = SearchDataObject::create('search', 'NoHit');
        $newQuestions = $adapter->searchQuestions($search);
        $this->assertEquals(get_class($newQuestions), Collection::class);
        $this->assertCount(0, $newQuestions);
    }

    /**
     * @test
     */
    public function getQuestionsWithEmptySearchString()
    {
        $this->expectException(RequestException::class);

        $client = $this->getClient([
            new Response(StatusCode::BAD_REQUEST, [],
                '{"timestamp": "2018-07-02T11:05:54.215+0000","status": 400,"error": "Bad Request","message": "Need to specify either searchString, keywords or questionSetId","path": "/v1/questions"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $adapter->searchQuestions(null);
    }

    /**
     * @test
     */
    public function getAnswersWithSearch()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method("request")
            ->with("GET", QuestionBankAdapter::ANSWERS, ['query' => ['search' => 'Correct']])
            ->willReturn(new Response(StatusCode::OK, [],
                '[{"metadata": {"keywords": [],"images": []},"id": "cebcf2f0-b233-4615-ac65-70d1af015f9a","questionId": "a184acf1-4c78-4f43-9a44-aad294dcc146","description": "Correct answer","correctness": 100}]'));

        $search = SearchDataObject::create('search', 'Correct');
        /** @var QuestionBankAdapter $adapter */
        $adapter = new QuestionBankAdapter($client);
        $answers = $adapter->searchAnswers($search);
        $this->assertEquals(get_class($answers), Collection::class);
        $answer = $answers->first();
        $this->assertCount(1, $answers);
        $this->assertEquals(get_class($answer), AnswerDataObject::class);
        $this->assertEquals("Correct answer", $answer->text);
    }

    /**
     * @test
     */
    public function getAnswersWithEmptySearchString()
    {
        $this->expectException(RequestException::class);
        $client = $this->getClient([
            new Response(StatusCode::BAD_REQUEST, [],
                '{"timestamp": "2018-07-02T11:20:27.615+0000","status": 400,"error": "Bad Request","message": "Need to specify either searchString, keywords or questionId","path": "/v1/answers"}'),
        ]);

        /** @var ClientInterface $client */
        $adapter = new QuestionBankAdapter($client);
        $adapter->searchAnswers(null);
    }
}
