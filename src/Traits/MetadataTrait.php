<?php

namespace Cerpus\QuestionBankClient\Traits;


use Cerpus\QuestionBankClient\DataObjects\MetadataDataObject;

trait MetadataTrait
{
    private ?MetadataDataObject $metadata = null;

    /**
     * @param  MetadataDataObject  $metadata
     * @return void
     */
    public function addMetadata(MetadataDataObject $metadata) :void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return MetadataDataObject|null
     */
    public function getMetadata() :?MetadataDataObject
    {
        return $this->metadata;
    }
}
