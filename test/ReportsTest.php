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

}

class CampaignPerformanceReport extends \ebussola\adwords\reports\reportdefinition\ReportDefinition {

    public function __construct(\ReportDefinition $report_definition) {
        parent::__construct($report_definition);

        $this->reportType = 'CAMPAIGN_PERFORMANCE_REPORT';
        $this->selector = new \Selector();
        $this->selector->fields = array('Amount', 'AverageCpc', 'AveragePosition', 'CampaignName', 'Clicks');
    }

}