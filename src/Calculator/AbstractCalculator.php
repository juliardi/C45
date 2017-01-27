<?php

namespace C45\Calculator;

use DataReader\IFace\DataReaderInterface;

abstract class AbstractCalculator
{
    /**
     * @var \DataReader\IFace\DataReaderInterface
     */
    protected $reader;

    protected $targetAttribute;
    protected $targetValues;
    protected $targetCount;

    public function __construct(DataReaderInterface $reader, $targetAttribute)
    {
        $this->reader = $reader;
        $this->setTargetAttribute($targetAttribute);
    }

    public function setTargetAttribute($targetAttributeName)
    {
        $this->targetAttribute = $targetAttributeName;
        $this->targetValues = $this->getAttributeValues($this->targetAttribute);

        foreach ($this->targetValues as $value) {
            $criteria[$this->targetAttribute] = $value;
            $this->targetCount[$value] = $this->reader->countByCriteria($criteria);
        }
    }

    protected function getAttributeValues($attributeName)
    {
        return $this->reader->getClasses([$attributeName])[$attributeName];
    }

    protected function getAttributeNames($criteria)
    {
        $attributeNames = $this->reader->getAttributes();

        foreach ($criteria as $key => $value) {
            $idx = array_search($key, $attributeNames);
            if ($idx !== false) {
                unset($attributeNames[$idx]);
            }
        }

        return $attributeNames;
    }
}
