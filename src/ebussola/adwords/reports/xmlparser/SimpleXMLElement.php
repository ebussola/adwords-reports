<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:17
 */

namespace ebussola\adwords\reports\xmlparser;


use ebussola\adwords\reports\Reports;
use ebussola\adwords\reports\XMLParser;

class SimpleXMLElement implements XMLParser {

    /**
     * Must be in accordance to adwords, see: https://developers.google.com/adwords/api/docs/guides/reporting
     *
     * @param array $arr
     *
     * @return string
     */
    public function arrayToXml($arr) {
        $report_definition = new \SimpleXMLElement('<reportDefinition/>');
        $report_definition->addAttribute('xmlns', 'https://adwords.google.com/api/adwords/cm/'.Reports::API_VERSION);
        $this->recursiveArrayToElementLvl1($report_definition, $arr);

        return str_replace("\n", '', $report_definition->asXML());
    }

    /**
     * @param \SimpleXMLElement $parent
     * @param array             $arr
     */
    private function recursiveArrayToElementLvl1(\SimpleXMLElement $parent, $arr) {
        foreach ($arr as $element_name => $element_value) {
            if (is_array($element_value)) {

                switch ($element_name) {
                    case 'fields' :
                    case 'values' :
                        $this->simpleArray2El($parent, $element_name, $element_value);
                        break;

                    default :
                        $el = $parent->addChild($element_name);
                        $this->recursiveArrayToElementLvl1($el, $element_value);
                        break;
                }

            } else {

                $parent->addChild($element_name, $element_value);
            }
        }
    }

    /**
     * @param \SimpleXMLElement $parent
     * @param                   $element_value
     * @param                   $element_name
     */
    private function recursiveArrayToElementLvl2(\SimpleXMLElement $parent, $element_value) {
        foreach ($element_value as $element_name => $value) {
            if (is_array($value)) {
                $el = $parent->addChild($element_name);
                $this->recursiveArrayToElementLvl2($el, $value, $element_name);
            } else {
                $parent->addChild($element_name, $value);
            }
        }
    }

    /**
     * @param \SimpleXMLElement $parent
     * @param string            $name
     * @param array             $values
     */
    private function simpleArray2El(\SimpleXMLElement $parent, $name, $values) {
        foreach ($values as $value) {
            $parent->addChild($name, $value);
        }
    }

}