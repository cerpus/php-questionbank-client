<?php

namespace Cerpus\QuestionBankClient;

use Cerpus\QuestionBankClient\Contracts\QuestionBankContract;
use Illuminate\Support\Facades\Facade;

/**
 * Class QuestionBankClient
 * @package Cerpus\QuestionBankClient
 *
 */
class QuestionBankClient extends Facade
{

    /**
     * @var string
     */
    static $alias = "questionbank-client";
    protected $defer = true;

    /**
     * @return string
     */
    public static function getConfigPath()
    {
        return self::getBasePath().'/src/Config/'.self::$alias.'.php';
    }

    /**
     * @return string
     */
    public static function getBasePath()
    {
        return dirname(__DIR__);
    }

    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return QuestionBankContract::class;
    }
}
