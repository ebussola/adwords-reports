<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 31/10/13
 * Time: 09:14
 */

namespace ebussola\adwords\reports;

/**
 * Interface ReportDefinition
 * @package ebussola\adwords\reports
 *
 * @property \Selector $selector
 * @property string $reportName
 * @property string $reportType
 * @property bool $hasAttachment
 * @property string $dateRangeType
 * @property string $downloadFormat
 * @property string $creationTime
 * @property bool $includeZeroImpressions
 */
interface ReportDefinition {

    /**
     * @return array
     */
    public function toArray();

} 