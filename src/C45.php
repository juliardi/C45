<?php

namespace C45;

use C45\Calculator\GainCalculator;
use C45\Calculator\GainRatioCalculator;
use C45\Calculator\SplitInfoCalculator;
use DataReader\CSV\Reader;

/**
 * @author Juliardi <ardi93@gmail.com>
 */
class C45
{
    /**
     * @var array
     */
    const REQUIRED_CONFIG = [
        'targetAttribute',
        'trainingFile',
        'splitCriterion',
    ];

    /**
     * @var int
     */
    const SPLIT_GAIN = 0;

    /**
     * @var int
     */
    const SPLIT_GAIN_RATIO = 1;

    /**
     * @var array
     */
    protected $config;

    /**
     * Determines the split criterion used in calculation.
     * It must be set to either C45::SPLIT_GAIN or C45::SPLIT_GAIN_RATIO.
     * @var int
     */
    protected $splitCriterion;

    /**
     * @var string Target attribute's name
     */
    protected $targetAttribute;

    /**
     * @var array Target attribute's values
     */
    protected $targetValues;

    /**
     * @var array Total target's count split by target value
     */
    protected $targetCount;

    /**
     * @var string
     */
    protected $trainingFile;

    /**
     * @var \DataReader\IFace\DataReaderInterface
     */
    protected $training;

    /**
     * @var GainCalculator
     */
    private $gainCalculator;

    /**
     * @var SplitInfoCalculator
     */
    private $splitInfoCalculator;

    /**
     * @var GainRatioCalculator
     */
    private $gainRatioCalculator;

    /**
     * @param array $config A configuration array. It must contains keys name
     * as described in C45::REQUIRED_CONFIG.
     */
    public function __construct(array $config = [])
    {
        if ($this->validateConfig($config)) {
            $this->config = $config;
            $this->assignConfig($config);
            $this->initTraining();
            $this->targetValues = $this->getAttributeValues($this->targetAttribute);

            foreach ($this->targetValues as $value) {
                $criteria[$this->targetAttribute] = $value;
                $this->targetCount[$value] = $this->training->countByCriteria($criteria);
            }

            $this->gainCalculator = new GainCalculator($this->training, $this->targetAttribute);
            $this->splitInfoCalculator = new SplitInfoCalculator($this->training, $this->targetAttribute);
            $this->gainRatioCalculator = new GainRatioCalculator($this->training, $this->targetAttribute);
        }
    }

    /**
     * Validates configuration values.
     * @param array $config
     * @return bool
     * @throws InvalidConfigException
     */
    private function validateConfig(array $config)
    {
        foreach (self::REQUIRED_CONFIG as $value) {
            if (!isset($config[$value])) {
                throw new InvalidConfigException(sprintf("Parameter '%s' is required in C45 configuration", $value));

                return false;
            }
        }

        return true;
    }

    /**
     * Assigns configuration values to its respective place.
     * @param  array  $config
     */
    private function assignConfig(array $config)
    {
        foreach (self::REQUIRED_CONFIG as $value) {
            $this->$value = $config[$value];
        }
    }

    /**
     * Initializes \DataReader\IFace\DataReaderInterface object to reads training file
     */
    private function initTraining()
    {
        $this->training = new Reader($this->trainingFile);
    }

    /**
     * Builds decision tree using C4.5 algorithm.
     * @param array $criteria
     * @return TreeNode
     */
    public function buildTree(array $criteria = [])
    {
        $treeNode = new TreeNode();
        // PRUNING-PRUNINGAN
        // $classProb = $this->calculateClassProbability($criteria);
        // $biggestClass = $this->getBiggestArrayAttribute($classProb);
        //
        // if ($classProb[$biggestClass] > 0.85) {
        //     $treeNode->setAttribute($this->targetAttribute);
        //     $treeNode->addChild('result', $biggestClass);
        //     $treeNode->setIsLeaf(true);
        //
        //     return $treeNode;
        // }
        // END of PRUNING-PRUNINGAN

        $checkClass = $this->isBelongToOneClass($criteria);

        if ($checkClass['return']) {
            $treeNode->setAttribute($this->targetAttribute);
            $treeNode->addChild('result', $checkClass['class']);
            $treeNode->setIsLeaf(true);

            return $treeNode;
        }

        $splitCriterion = $this->calculateSplitCriterion($criteria);

        $bestAttrName = $this->getBiggestArrayAttribute($splitCriterion);
        $bestAttrValues = $this->getAttributeValues($bestAttrName);

        $treeNode->setAttribute($bestAttrName);

        unset($splitCriterion[$bestAttrName]);

        foreach ($bestAttrValues as $value) {
            $criteria[$bestAttrName] = $value;
            $targetCount = $this->countTargetByCriteria($criteria);
            $treeNode->addClassesCount($value, $targetCount);

            if (array_sum($targetCount) == 0) {
                $targetCount2 = $this->countTargetByCriteria([$bestAttrName => $value]);
                $biggestClass = $this->getBiggestArrayAttribute($targetCount2);

                $child = new TreeNode();
                $child->setParent($treeNode);
                $child->setAttribute($this->targetAttribute);
                $child->addChild('result', $biggestClass);
                $child->setIsLeaf(true);

                $treeNode->addChild($value, $child);
            } elseif (!empty($splitCriterion)) {
                $child = $this->buildTree($criteria);
                $child->setParent($treeNode);

                $treeNode->addChild($value, $child);
            } else {
                $classProb = $this->calculateClassProbability($criteria);
                $biggestClass = $this->getBiggestArrayAttribute($classProb);

                $child = new TreeNode();
                $child->setParent($treeNode);
                $child->setAttribute($this->targetAttribute);
                $child->addChild('result', $biggestClass);
                $child->setIsLeaf(true);

                $treeNode->addChild($value, $child);
            }
        }

        return $treeNode;
    }

    /**
     * Calculates the split criterion by referring to C45::splitCriterion value.
     * @param  array  $criteria
     * @return float The gain or gain ratio value
     */
    private function calculateSplitCriterion($criteria = [])
    {
        $gain = $this->gainCalculator->calculateGainAllAttributes($criteria);

        if ($this->splitCriterion == self::SPLIT_GAIN) {
            return $gain;
        } else {
            $splitInfo = $this->splitInfoCalculator->calculateSplitInfoAllAttributes($criteria);
            $gainRatio = $this->gainRatioCalculator->calculateGainRatio($gain, $splitInfo);

            return $gainRatio;
        }
    }

    /**
     * @param  array  $criteria
     * @return float
     */
    private function calculateClassProbability(array $criteria)
    {
        $cTarget = $this->countTargetByCriteria($criteria);
        $total = array_sum($cTarget);
        $classProb = [];

        foreach ($this->targetValues as $value) {
            $classProb[$value] = $this->classProbability($cTarget[$value], $total);
        }

        return $classProb;
    }

    /**
     * Calculates class probability of a node
     * @param  int $cTargetClass
     * @param  int $total
     * @return float
     */
    private function classProbability($cTargetClass, $total)
    {
        if ($total == 0) {
            return 0;
        }

        return $cTargetClass / $total;
    }

    /**
     * Checks if a leaf node belongs to only one type of class
     * @param  array   $criteria
     * @return bool
     */
    private function isBelongToOneClass(array $criteria)
    {
        $countAll = $this->training->countByCriteria($criteria);

        foreach ($this->targetValues as $value) {
            $criteria[$this->targetAttribute] = $value;
            $countByTarget = $this->training->countByCriteria($criteria);
            unset($criteria[$this->targetAttribute]);
            if ($countAll === $countByTarget) {
                return [
                    'return' => true,
                    'class' => $value,
                ];
            }
        }

        return ['return' => false];
    }

    /**
     * @param  array  $array
     * @return string Array's key of the biggest value in the array
     */
    private function getBiggestArrayAttribute(array $array)
    {
        array_multisort($array, SORT_DESC);
        reset($array);
        $key = key($array);

        return $key;
    }

    /**
     * @param  array  $criteria
     * @return int[]
     */
    private function countTargetByCriteria(array $criteria)
    {
        $targetCount = [];

        foreach ($this->targetValues as $value) {
            $criteria[$this->targetAttribute] = $value;
            $targetCount[$value] = $this->training->countByCriteria($criteria);
        }

        unset($criteria[$this->targetAttribute]);

        return $targetCount;
    }

    /**
     * @param  string $attributeName
     * @return mixed
     */
    private function getAttributeValues($attributeName)
    {
        return $this->training->getClasses([$attributeName])[$attributeName];
    }
}
