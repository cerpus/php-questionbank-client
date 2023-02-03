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


    /**
     * @return array
     */
    public function getImages()
    {
        if (! empty($this->metadata->images)) {
            return $this->metadata->images;
        }
        return [];
    }

    /**
     * @param $index
     * @return string|null
     */
    public function getImageAt($index)
    {
        if (! empty($this->metadata->images[$index])) {
            return $this->metadata->images[$index];
        }
        return null;
    }
}
