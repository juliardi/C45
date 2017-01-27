<?php

namespace C45\Calculator;

class SplitInfoCalculator extends AbstractCalculator
{
    public function calculateSplitInfoAllAttributes($criteria = [])
    {
        $attributeNames = $this->getAttributeNames($criteria);

        $splitInfo = [];

        foreach ($attributeNames as $value) {
            $splitInfo[$value] = $this->calculateSplitInfoOfAttribute($value, $criteria);
        }

        return $splitInfo;
    }

    public function calculateSplitInfoOfAttribute($attributeName, $criteria = [])
    {
        $attributeCount = [];

        $attributeValues = $this->getAttributeValues($attributeName);

        foreach ($attributeValues as $value) {
            $criteria[$attributeName] = $value;
            $attributeCount[$value] = $this->reader->countByCriteria($criteria);
        }
        $splitInfo = $this->splitInfo($attributeCount);

        return $this->splitInfo($attributeCount);
    }

    /**
     * Calculates splitInfo.
     *
     * @param array $values Array that contain attribute value's count
     *                      Example :
     *                      ```
     *                      [
     *                      'value1' => 10,
     *                      'value2' => 13,
     *                      ...,
     *                      'valueN' => count,
     *                      ]
     *
     * ```
     *
     * @return float
     */
    private function splitInfo(array $values)
    {
        $result = 0;
        $sum = array_sum($values);

        foreach ($values as $value) {
            if ($value > 0) {
                $proportion = $value / $sum;
                $result += -1 * ($proportion * log($proportion, 2));
            }
        }

        return $result;
    }
}
