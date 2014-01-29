<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 17:31
 */

namespace ebussola\adwords\reports\reportdefinition;

/**
 * Decorator of \ReportDefinition
 *
 * Class ReportDefinition
 * @package ebussola\adwords\reports\reportdefinition
 */
class ReportDefinition implements \ebussola\adwords\reports\ReportDefinition {

    /**
     * @var array
     * field_name => field_type
     *
     * int, float or string
     */
    public $field_types;

    /**
     * @var \ReportDefinition
     */
    private $report_definition;

    public function __construct(\ReportDefinition $report_definition) {
        $this->report_definition = $report_definition;
    }

    public function toArray() {
        $selector_arr = (array) $this->report_definition->selector;

        $predicates_arr = array();
        foreach ($this->report_definition->selector->predicates as $predicate) {
            $predicates_arr[] = (array) $predicate;
        }

        $date_range_arr = (array) $this->report_definition->selector->dateRange;

        $report_definition_arr = (array) $this->report_definition;

        $selector_arr['predicates'] = $predicates_arr;
        $selector_arr['dateRange'] = $date_range_arr;
        $report_definition_arr['selector'] = $selector_arr;

        return $report_definition_arr;
    }

    public function __set($name, $value) {
        $this->report_definition->{$name} = $value;
    }

    public function __get($name) {
        return $this->report_definition->{$name};
    }

}