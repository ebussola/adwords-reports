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
            'id' => null,
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
<reportDefinition xmlns="https://adwords.google.com/api/adwords/cm/v201406">
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

    public function testReportXmlToArray() {
        $xml = <<<XML
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<report>
    <report-name name="Foo Report" />
    <date-range date="Oct 22, 2013-Nov 1, 2013" />
    <table>
        <columns>
            <column name="budget" display="Budget" />
            <column name="avgCPC" display="Avg. CPC" />
            <column name="avgPosition" display="Avg. position" />
            <column name="campaign" display="Campaign" />
            <column name="clicks" display="Clicks" />
        </columns>
        <row budget="100.00" avgCPC="0.00" avgPosition="0.0" campaign="APSA - Pesquisa" clicks="0" />
        <row budget="12.00" avgCPC="0.84" avgPosition="2.3" campaign="Apsa - Locação BA - Abril 2011" clicks="430" />
        <row budget="20.00" avgCPC="1.23" avgPosition="1.6" campaign="Apsa - Condomínio PE - Abril 2011" clicks="174" />
    </table>
</report>
XML;

        $expected = array(
            (object) array(
                'budget'      => "100.00",
                'avgCPC'      => "0.00",
                'avgPosition' => "0.0",
                'campaign'    => "APSA - Pesquisa",
                'clicks'      => "0"
            ),
            (object) array(
                'budget'      => "12.00",
                'avgCPC'      => "0.84",
                'avgPosition' => "2.3",
                'campaign'    => "Apsa - Locação BA - Abril 2011",
                'clicks'      => "430"
            ),
            (object) array(
                'budget'      => "20.00",
                'avgCPC'      => "1.23",
                'avgPosition' => "1.6",
                'campaign'    => "Apsa - Condomínio PE - Abril 2011",
                'clicks'      => "174"
            )
        );

        $arr = $this->xml_parser->reportXmlToArray($xml);

        $this->assertEquals($expected, $arr);
    }

}
