<?php

namespace C45\Calculator;

/**
 *
 */
class GainRatioCalculator extends AbstractCalculator
{
    public function calculateGainRatio(array $gain, array $splitInfo)
    {
        $gainRatio = [];

        foreach ($gain as $key => $value) {
            if ($splitInfo[$key] == 0) {
                $gainRatio[$key] = 0;
            } else {
                $gainRatio[$key] = $value / $splitInfo[$key];
            }
        }

        return $gainRatio;
    }
}
