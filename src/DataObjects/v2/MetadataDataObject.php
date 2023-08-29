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
    public $keywords = [];
    public array $languages = [];
    public array $subject = [];
    public array $age_levels = [];
}
