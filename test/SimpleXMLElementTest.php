<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:26
 */

class SimpleXMLElementTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \ebussola\adwords\reports\XMLParser
     */
    private $xml_parser;

    public function setUp() {
        $this->xml_parser = new \ebussola\adwords\reports\xmlparser\SimpleXMLElement();
    }

    public function testArrayToXml() {
        $arr = array(
            'selector' => array(
                'fields' => array('CampaignId', 'Id', 'Impressions', 'Clicks', 'Cost'),
                'predicates' => array(
                    array(
                        'field' => 'Status',
                        'operator' => 'IN',
                        'values' => array('ENABLED', 'PAUSED')
                    ),
                    array(
                        'field' => 'Clicks',
                        'operator' => 'GREATER_THAN',
                        'values' => '0'
                    )
                ),
                'dateRange' => array(
                    'min' => '20131001',
                    'max' => '20131029'
                )
            ),
            'reportName' => 'Custom Adgroup Performance Report',
            'reportType' => 'ADGROUP_PERFORMANCE_REPORT',
            'dateRangeType' => 'CUSTOM_DATE',
            'downloadFormat' => 'CSV'
        );

        $xml_str = $this->xml_parser->arrayToXml($arr);

        $expected = <<<XML
<?xml version="1.0"?>
<reportDefinition xmlns="https://adwords.google.com/api/adwords/cm/v201309">
  <selector>
    <fields>CampaignId</fields>
    <fields>Id</fields>
    <fields>Impressions</fields>
    <fields>Clicks</fields>
    <fields>Cost</fields>
    <predicates>
      <field>Status</field>
      <operator>IN</operator>
      <values>ENABLED</values>
      <values>PAUSED</values>
    </predicates>
    <predicates>
      <field>Clicks</field>
      <operator>GREATER_THAN</operator>
      <values>0</values>
    </predicates>
    <dateRange>
      <min>20131001</min>
      <max>20131029</max>
    </dateRange>
  </selector>
  <reportName>Custom Adgroup Performance Report</reportName>
  <reportType>ADGROUP_PERFORMANCE_REPORT</reportType>
  <dateRangeType>CUSTOM_DATE</dateRangeType>
  <downloadFormat>CSV</downloadFormat>
</reportDefinition>
XML;

        $expected = str_replace("\n", '', $expected);
        $expected = str_replace("  ", '', $expected);
        $this->assertEquals($expected, $xml_str);
    }

}
 