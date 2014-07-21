<?php
use ebussola\adwords\reports\Reports;

/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 31/10/13
 * Time: 16:16
 */

class ReportsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Reports
     */
    private $reports;

    public function setUp() {
        $config = require(__DIR__.'/config.php');
        $client = new \Guzzle\Service\Client();
        $xml_parser = new \ebussola\adwords\reports\xmlparser\SimpleXMLElement();
        $this->reports = new Reports($client, $xml_parser,
            $config['auth_token'], $config['developer_token'], $config['customer_id']);
    }

    public function testSimpleDownloadReports() {
        $campaign_report = new CampaignPerformanceReport(new ReportDefinition());
        $date_start = new \DateTime('-10 days');
        $date_end = new \DateTime('today');
        $this->reports->buildReportDefinition('Foo Report', array(), $date_start, $date_end, $campaign_report);
        $reports = $this->reports->downloadReports(array($campaign_report));

        foreach ($reports as $report) {
            foreach ($report as $row) {
                $this->assertObjectHasAttribute('budget', $row);
                $this->assertObjectHasAttribute('avgCPC', $row);
                $this->assertObjectHasAttribute('avgPosition', $row);
                $this->assertObjectHasAttribute('campaign', $row);
                $this->assertObjectHasAttribute('clicks', $row);
            }
        }
    }

    public function testMultipleDownloadReports() {

        $report_definitions = array();
        for ($i=0 ; $i<=100 ; $i++) {
            $campaign_report = new CampaignPerformanceReport(new ReportDefinition());
            $date_start = new \DateTime('-60 days');
            $date_end = new \DateTime('today');
            $this->reports->buildReportDefinition('Foo Report', array(), $date_start, $date_end, $campaign_report);

            $report_definitions[] = $campaign_report;
        }

        $reports = $this->reports->downloadReports($report_definitions);

        foreach ($reports as $report) {
            foreach ($report as $row) {
                $this->assertObjectHasAttribute('budget', $row);
                $this->assertObjectHasAttribute('avgCPC', $row);
                $this->assertObjectHasAttribute('avgPosition', $row);
                $this->assertObjectHasAttribute('campaign', $row);
                $this->assertObjectHasAttribute('clicks', $row);
            }
        }

        return array(
            $report_definitions,
            $reports
        );
    }

    /**
     * @depends testMultipleDownloadReports
     */
    public function testFieldTypes($result) {
        list($report_definitions, $reports) = $result;

        foreach ($reports as $i => $report) {
            $report_definition = $report_definitions[$i];

            foreach ($report as $stats) {
                foreach ($stats as $field => $value) {

                    foreach ($report_definition->field_types as $rd_field => $rd_type) {
                        if ($rd_field == $field) {
                            switch ($rd_type) {
                                case 'int' :
                                    $this->assertTrue(is_integer($value));
                                    break;

                                case 'float' :
                                    $this->assertTrue(is_float($value));
                                    break;

                                case 'micro' :
                                    $this->assertTrue($value < 1000000);
                                    break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function testFieldTypeDoNotSetted() {
        $this->setExpectedException('\ebussola\adwords\reports\exception\FieldTypeNotDefinedException');

        $campaign_report = new CampaignPerformanceReportNoType(new ReportDefinition());
        $date_start = new \DateTime('-10 days');
        $date_end = new \DateTime('today');
        $this->reports->buildReportDefinition('Foo Report', array(), $date_start, $date_end, $campaign_report);
        $reports = $this->reports->downloadReports(array($campaign_report));

        foreach ($reports as $report) {
            foreach ($report as $row) {
                $this->assertObjectHasAttribute('budget', $row);
                $this->assertObjectHasAttribute('avgCPC', $row);
                $this->assertObjectHasAttribute('avgPosition', $row);
                $this->assertObjectHasAttribute('campaign', $row);
                $this->assertObjectHasAttribute('clicks', $row);
            }
        }
    }

}

class CampaignPerformanceReport extends \ebussola\adwords\reports\reportdefinition\ReportDefinition {

    public function __construct(\ReportDefinition $report_definition) {
        parent::__construct($report_definition);

        $this->reportType = 'CAMPAIGN_PERFORMANCE_REPORT';
        $this->selector = new \Selector();
        $this->selector->fields = array(
            'Amount',
            'AverageCpc',
            'AveragePosition',
            'CampaignName',
            'Clicks'
        );

        $this->field_types = array(
            'budget'      => 'micro',
            'avgCPC'      => 'micro',
            'avgPosition' => 'float',
            'campaign'    => 'string',
            'clicks'      => 'int'
        );
    }

}
class CampaignPerformanceReportNoType extends \ebussola\adwords\reports\reportdefinition\ReportDefinition {

    public function __construct(\ReportDefinition $report_definition) {
        parent::__construct($report_definition);

        $this->reportType = 'CAMPAIGN_PERFORMANCE_REPORT';
        $this->selector = new \Selector();
        $this->selector->fields = array(
            'Amount',
            'AverageCpc',
            'AveragePosition',
            'CampaignName',
            'Clicks'
        );
    }

}