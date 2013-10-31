<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:19
 */

namespace ebussola\adwords\reports;


class Reports {

    const API_VERSION = 'v201309';

    public function buildReportDefinition(
        $report_name,
        array $predicates,
        \DateTime $date_start,
        \DateTime $date_end,
        \ReportDefinition $report_definition=null
    ) {

        if ($report_definition === null) {
            $report_definition = new \ReportDefinition();
        }

        $report_definition->selector->predicates = $predicates;

        $report_definition->selector->dateRange = new \DateRange($date_start->format('Ymd'), $date_end->format('Ymd'));
        $report_definition->reportName = $report_name;
        $report_definition->dateRangeType = 'CUSTOM_DATE';
        $report_definition->downloadFormat = 'GZIPPED_XML';

        return $report_definition;
    }

}