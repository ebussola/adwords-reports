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
        $this->recursiveArrayToElement($report_definition, $arr);

        return str_replace("\n", '', $report_definition->asXML());
    }

    /**
     * @param string $xml
     *
     * @return array
     */
    public function reportXmlToArray($xml) {
        $xml = new \SimpleXMLElement($xml);
        $result_arr = array();
        /** @var \SimpleXMLElement $row */
        foreach ($xml->table->row as $row) {
            $row_arr = array();
            foreach ($row->attributes() as $key => $value) {
                $row_arr[$key] = (string)$value;
            }
            $result_arr[] = (object) $row_arr;
        }

        return $result_arr;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @param array             $arr
     */
    private function recursiveArrayToElement(\SimpleXMLElement $parent, $arr) {
        foreach ($arr as $element_name => $element_value) {
            if (is_array($element_value)) {

                switch ($element_name) {
                    case false :
                    default :
                        $el = $parent->addChild($element_name);
                        $this->recursiveArrayToElement($el, $element_value);
                        break;

                    case 'predicates' :
                        foreach ($element_value as $predicate) {
                            $el = $parent->addChild($element_name);
                            $this->recursiveArrayToElement($el, $predicate);
                        }
                        break;

                    case 'fields' :
                    case 'values' :
                        $this->simpleArray2El($parent, $element_name, $element_value);
                        break;
                }

            } else if ($element_value != null) {

                $parent->addChild($element_name, $element_value);
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