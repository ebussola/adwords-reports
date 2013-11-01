<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:13
 */

namespace ebussola\adwords\reports;

/**
 * Interface XMLParser
 * @package ebussola\adwords\reports
 */
interface XMLParser {

    /**
     * Must be in accordance to adwords, see: https://developers.google.com/adwords/api/docs/guides/reporting
     *
     * @param array $arr
     *
     * @return string
     */
    public function arrayToXml($arr);

    /**
     * @param string $xml
     *
     * @return array
     */
    public function reportXmlToArray($xml);

} 