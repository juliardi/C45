<?php

namespace C45;

use Exception;

/**
 * @author Juliardi <ardi93@gmail.com>
 */
class TreeNode
{
    /**
     * @var self Parent's Node
     */
    protected $parent;

    /**
     * @var string Attribute name this node represent
     */
    protected $attribute;

    /**
     * @var array Attribute's values
     */
    protected $values;

    /**
     * @var array Classes count for this node and its child
     */
    protected $classesCount;

    /**
     * @var bool
     */
    protected $isLeaf;

    /**
     * @param self $parent
     */
    public function setParent(TreeNode $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return self|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets attribute name.
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    public function addClassesCount($valueName, array $classesCount)
    {
        $this->classesCount[$valueName] = $classesCount;
    }

    public function setIsLeaf($isLeaf)
    {
        $this->isLeaf = $isLeaf;
    }

    public function getIsLeaf()
    {
        return $this->isLeaf;
    }

    /**
     * Adds child TreeNode.
     *
     * @param string $value
     * @param mixed  $child
     */
    public function addChild($value, $child)
    {
        if (!isset($this->values)) {
            $this->values = [];
        }
        $this->values[$value] = $child;
    }

    /**
     * @return self
     */
    public function getChild($value)
    {
        if ($this->hasValue($value)) {
            return $this->values[$value];
        }
    }

    /**
     * @return string
     */
    public function getAttributeName()
    {
        return $this->attribute;
    }

    public function removeValue($value)
    {
        if ($this->hasValue($value)) {
            unset($this->values[$value]);
        }
    }

    public function hasValue($value)
    {
        if (!isset($this->values)) {
            return false;
        }

        return array_key_exists($value, $this->values);
    }

    /**
     * Classifies a data.
     *
     * @param array $data
     *
     * @return string
     */
    public function classify(array $data)
    {
        if (isset($data[$this->attribute])) {
            $attrValue = $data[$this->attribute];
            if (!$this->hasValue($attrValue)) {
                return 'unclassified';
            }
            $child = $this->values[$attrValue];
            if (!$child->getIsLeaf()) {
                return $child->classify($data);
            } else {
                return $child->getChild('result');
            }
        }
    }

    public function __toArray()
    {
        $arrObj = [];
        $arrObj['attribute'] = $this->attribute;
        foreach ($this->values as $key => $value) {
            if (!is_null($value)) {
                if ($value instanceof self) {
                    $arrObj['values'][$key] = $value->__toArray();
                }
            }
        }

        return $arrObj;
    }

    /**
     * Generates a string representation of the tree.
     *
     * @param string $tabs
     *
     * @return string
     */
    public function toString($tabs = '')
    {
        $result = '';

        foreach ($this->values as $key => $child) {
            $result .= $tabs.$this->attribute.' = '.$key;

            if ($child->getIsLeaf()) {
                $classCount = $this->getClassesCountAsString($key);
                $result .= ' : '.$child->getChild('result').' '.$classCount."\n";
            } else {
                $result .= "\n";
                $result .= $child->toString($tabs."|\t");
            }
        }

        return $result;
    }

    private function getClassesCountAsString($attributeValue)
    {
        $result = '(';
        $total = array_sum($this->classesCount[$attributeValue]);

        foreach ($this->classesCount[$attributeValue] as $key => $value) {
            $result .= $value.'/';
        }

        $result .= $total.')';

        return $result;
    }

    /**
     * Saves tree to file.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function saveToFile($filename)
    {
        $str = serialize($this);

        $handle = @fopen($filename, 'wb');
        @fwrite($handle, $str);

        return @fclose($handle);
    }

    /**
     * Creates TreeNode object from file.
     *
     * @param string Full path filename
     *
     * @return self
     */
    public static function createFromFile($filename)
    {
        try {
            $obj_data = file_get_contents($filename);
            $obj = unserialize($obj_data);

            return $obj;
        } catch (Exception $exc) {
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());

            return;
        }
    }
}
