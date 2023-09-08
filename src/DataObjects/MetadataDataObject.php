<?php

namespace Cerpus\QuestionBankClient\DataObjects;


use Cerpus\Helper\Traits\CreateTrait;

/**
 * Class MetadataDataObject
 * @package Cerpus\QuestionBankClient\DataObjects
 *
 * @method static MetadataDataObject create($attributes = null)
 */
class MetadataDataObject
{
    use CreateTrait;

    public $keywords = [];
    public $images = [];
    public array $languages = [];
    public array $subjects = [];
    public array $age_levels = [];
}
