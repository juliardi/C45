<?php

namespace C45\Calculator;

class GainCalculator extends AbstractCalculator
{
    /**
     * Calculates all attributes gain.
     *
     * @param array $criteria
     *
     * @return float[] Array of gain
     */
    public function calculateGainAllAttributes($criteria = [])
    {
        $attributeNames = $this->getAttributeNames($criteria);

        $gain = [];

        foreach ($attributeNames as $value) {
            if ($value != $this->targetAttribute) {
                $gain[$value] = $this->calculateGainOfAttribute($value, $criteria);
            }
        }

        return $gain;
    }

    /**
     * Calculates gain of an attribute.
     *
     * @param string $attributeName
     * @param array  $criteria
     *
     * @return float Gain value of the attribute
     */
    public function calculateGainOfAttribute($attributeName, $criteria = [])
    {
        $gain = 0;
        $attributeCount = [];
        $attributeValues = $this->getAttributeValues($attributeName);

        foreach ($attributeValues as $value) {
            $criteria[$attributeName] = $value;
            foreach ($this->targetValues as $targetValue) {
                $criteria[$this->targetAttribute] = $targetValue;
                $attributeCount[$value][$targetValue] = $this->reader->countByCriteria($criteria);
            }
        }

        $gain = $this->gain($this->targetCount, $attributeCount);

        return $gain;
    }

    /**
     * Calculates gain.
     *
     * @param array $classifier_values Array of classes count in format
     *                                 ```
     *                                 [
     *                                 'class1' => 100,
     *                                 'class2' => 200,
     *                                 ......,
     *                                 'classN' => count,
     *                                 ]
     *                                 ```
     * @param array $values            Array of classes count in format
     *                                 ```
     *                                 [
     *                                 'attribute1' => [
     *                                 'class1' => 100,
     *                                 'class2' => 200,
     *                                 ......,
     *                                 'classN' => count,
     *                                 ],
     *                                 'attribute2' => [
     *                                 'class1' => 100,
     *                                 'class2' => 200,
     *                                 ......,
     *                                 'classN' => count,
     *                                 ],
     *                                 .....,
     *                                 'attributeN' => [
     *                                 'class1' => 100,
     *                                 'class2' => 200,
     *                                 ......,
     *                                 'classN' => count,
     *                                 ],
     *                                 ]
     *                                 ```
     */
    private function gain($classifier_values, $values)
    {
        $entropy_all = $this->entropy($classifier_values);
        $total_records = 0;

        foreach ($values as $sub_values) {
            $total_records += array_sum($sub_values);
        }

        $gain = 0;
        foreach ($values as $sub_values) {
            try {
                $sub_total_values = array_sum($sub_values);
                $entropy = $this->entropy($sub_values);
                $gain += ($sub_total_values / $total_records) * $entropy;
            } catch (\Exception $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }
        }

        $gain = $entropy_all - $gain;

        return $gain;
    }

    /**
     * Calculates entropy.
     *
     * @param array $values Array of classes count in format
     *                      ```
     *                      [
     *                      'class1' => 100,
     *                      'class2' => 200,
     *                      ......,
     *                      'classN' => count,
     *                      ]
     *                      ```
     *
     * @return float
     */
    private function entropy(array $values)
    {
        $result = 0;
        $sum = array_sum($values);

        foreach ($values as $value) {
            if ($value > 0) {
                $proportion = $value / $sum;
                $result += -($proportion * log($proportion, 2));
            }
        }

        return $result;
    }
}
