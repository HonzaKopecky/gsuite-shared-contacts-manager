<?php

namespace App\Model;

use Nette\SmartObject;

/** Abstract class which is the ancestor of all other attributes. All descendants must implement to/from XML conversions.
 */
abstract class Attribute {
	use SmartObject;

    /** Convert current attribute to XML
     * @return \SimpleXMLElement
     */
    abstract public function toXML(\SimpleXMLElement &$xml);

    /** Create new instance of attribute from XML
     * @param \SimpleXMLElement $xml
     * @return $this
     */
    abstract public function fromXML(\SimpleXMLElement &$xml);

    /**
     * @param \SimpleXMLElement $xml
     * @param string $elementName
     * @return null|\string
     */
    protected static function getChildValue(\SimpleXMLElement &$xml, $elementName) {
        $val = $xml->xpath('descendant::'.$elementName);
        if($val == null || !isset($val[0]))
            return null;
        return (string)$val[0];
    }
}