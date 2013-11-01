<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 31/10/13
 * Time: 09:09
 */

class ReportDefinitionTest extends \PHPUnit_Framework_TestCase {

    public function testToArray() {
        $original = new \ReportDefinition();
        $report_definition = new \ebussola\adwords\reports\reportdefinition\ReportDefinition($original);

        $report_definition->selector = new \Selector();
        $report_definition->selector->fields = array('CampaignId', 'Id', 'Impressions', 'Clicks', 'Cost');
        $report_definition->selector->predicates = array(
            new \Predicate('Status', 'IN', array('ENABLED', 'PAUSED')),
            new \Predicate('Clicks', 'GREATER_THAN', '0')
        );
        $report_definition->selector->dateRange = new DateRange('20131001', '20131029');

        $report_definition->reportName = 'Custom Adgroup Performance Report';
        $report_definition->reportType = 'ADGROUP_PERFORMANCE_REPORT';
        $report_definition->dateRangeType = 'CUSTOM_DATE';
        $report_definition->downloadFormat = 'CSV';

        $this->assertEquals(array(
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
                ),
                'ordering' => null,
                'paging' => null
            ),
            'reportName' => 'Custom Adgroup Performance Report',
            'reportType' => 'ADGROUP_PERFORMANCE_REPORT',
            'hasAttachment' => null,
            'dateRangeType' => 'CUSTOM_DATE',
            'downloadFormat' => 'CSV',
            'creationTime' => null,
            'includeZeroImpressions' => null
        ), $report_definition->toArray());
    }

}
 