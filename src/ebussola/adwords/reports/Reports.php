<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:19
 */

namespace ebussola\adwords\reports;

use ebussola\adwords\reports\exception\FieldTypeNotDefinedException;
use ebussola\adwords\reports\ReportDefinition;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Message\Response;

class Reports {

    const API_VERSION = 'v201309';

    const MAX_PARALLEL_DOWNLOADS = 40;

    const FILE_COUNTER = '/dev/shm/ebussola_adwords_reports_counter';
    const ALTERNATIVE_FILE_COUNTER = '/tmp/ebussola_adwords_reports_counter';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var XMLParser
     */
    private $xml_parser;

    /**
     * @var \SplFileObject
     */
    private $file_counter;

    /**
     * @var string
     */
    private $auth_token;

    /**
     * @var string
     */
    private $developer_token;

    /**
     * @var string
     */
    private $customer_id;

    /**
     * @param Client    $client
     * @param XMLParser $xml_parser
     * @param string    $auth_token
     * @param string    $developer_token
     * @param string    $customer_id
     */
    public function __construct(Client $client, XMLParser $xml_parser,
                                $auth_token, $developer_token, $customer_id) {

        $this->client = $client;
        $this->xml_parser = $xml_parser;
        $this->auth_token = $auth_token;
        $this->developer_token = $developer_token;
        $this->customer_id = $customer_id;

        if (file_exists('/dev/shm')) {
            $this->file_counter = new \SplFileObject(self::FILE_COUNTER, 'a+');
        } else {
            $this->file_counter = new \SplFileObject(self::ALTERNATIVE_FILE_COUNTER, 'a+');
        }
    }

    /**
     * @param ReportDefinition[] $report_definitions
     *
     * @return array
     * @throws \Exception
     */
    public function downloadReports($report_definitions) {

        $responses = array();
        $offset = 0;
        do {
            list($sliced_report_definitions_count, $requests) = $this->getNextRequests($report_definitions, $offset);

            // if $requests is none, wait a moment to free some slots and try again
            if (count($requests) == 0) {
                usleep(10000);
                continue;
            }

            try {
                $responses = array_merge($responses, $this->client->send($requests));
            } catch (MultiTransferException $e) {

                $this->freeUsedRequestCount($sliced_report_definitions_count);

                $messages = '';
                foreach ($e as $exception) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $element = new \SimpleXMLElement($exception->getResponse()->getBody('true'));
                    $api_error = $element->ApiError->type;
                    $trigger = $element->ApiError->trigger;

                    $messages .= $api_error. ' : ' .$trigger . "\n";
                }

                throw new \Exception($messages);
            }

            $this->freeUsedRequestCount($sliced_report_definitions_count);

        } while (count($responses) < count($report_definitions));

        $reports = array();
        /** @var Response[] $responses */
        foreach ($responses as $response) {
            $response_body = $response->getBody(true);
            $xml = gzdecode($response_body);
            $report = $this->xml_parser->reportXmlToArray($xml);

            $reports[] = $report;
        }

        $this->fixFieldTypes($reports, $report_definitions);

        return $reports;
    }

    /**
     * @param string       $field
     * @param string       $operator
     * @param string|array $value
     *
     * @return \Predicate
     */
    public function makePredicate($field, $operator, $value) {
        return new \Predicate($field, $operator, $value);
    }

    /**
     * @param string           $report_name
     * @param array            $predicates
     * @param \DateTime        $date_start
     * @param \DateTime        $date_end
     * @param ReportDefinition $report_definition
     *
     * @return ReportDefinition
     */
    public function buildReportDefinition(
        $report_name,
        array $predicates,
        \DateTime $date_start,
        \DateTime $date_end,
        ReportDefinition $report_definition=null
    ) {

        if ($report_definition === null) {
            $report_definition = new reportdefinition\ReportDefinition(new \ReportDefinition());
        }

        $report_definition->selector->predicates = $predicates;

        $report_definition->selector->dateRange = new \DateRange($date_start->format('Ymd'), $date_end->format('Ymd'));
        $report_definition->reportName = $report_name;
        $report_definition->dateRangeType = 'CUSTOM_DATE';
        $report_definition->downloadFormat = 'GZIPPED_XML';

        return $report_definition;
    }

    /**
     * @param ReportDefinition $report_definition
     *
     * @return \Guzzle\Http\Message\RequestInterface
     */
    private function buildRequest(ReportDefinition $report_definition) {
        $xml = $this->xml_parser->arrayToXml($report_definition->toArray());
        $request = $this->client->post('https://adwords.google.com/api/adwords/reportdownload/'.self::API_VERSION, array(
            'Authorization'    => $this->auth_token,
            'developerToken'   => $this->developer_token,
            'clientCustomerId' => $this->customer_id
        ), array(
            '__rdxml' => $xml
        ));

        return $request;
    }

    /**
     * Lock the counter and get the current available amount of slots
     *
     * @return int
     */
    private function lockAndGetAvailableRequestCount() {
        $this->file_counter->flock(LOCK_EX);

        $this->file_counter->rewind();
        $used_request_count = (int) $this->file_counter->fgets();

        $available_request_count = self::MAX_PARALLEL_DOWNLOADS - $used_request_count;

        return $available_request_count;
    }

    /**
     * Unlock the counter and update the counter with used slots
     *
     * @param int $sliced_report_definitions_count
     */
    private function unlock($sliced_report_definitions_count) {
        $this->file_counter->rewind();
        $used_request_count = (int)$this->file_counter->fgets();

        $new_used_request_count = $used_request_count + $sliced_report_definitions_count;

        $this->file_counter->ftruncate(0);
        $this->file_counter->fwrite($new_used_request_count);

        $this->file_counter->flock(LOCK_UN);
    }

    /**
     * Free the used slots
     *
     * @param $sliced_report_definitions_count
     */
    private function freeUsedRequestCount($sliced_report_definitions_count) {
        $this->file_counter->flock(LOCK_EX);

        $this->file_counter->rewind();
        $used_request_count = (int)$this->file_counter->fgets();
        $new_used_request_count = $used_request_count - $sliced_report_definitions_count;

        $this->file_counter->ftruncate(0);
        $this->file_counter->fwrite($new_used_request_count);

        $this->file_counter->flock(LOCK_UN);
    }

    /**
     * Get next requests based on available slots
     *
     * @param ReportDefinition[] $report_definitions
     * @param int $offset
     *
     * @return array
     */
    private function getNextRequests($report_definitions, &$offset) {
        $available_request_count = $this->lockAndGetAvailableRequestCount();

        /** @var ReportDefinition[] $sliced_report_definitions */
        $sliced_report_definitions = array_slice($report_definitions, $offset, $available_request_count);
        $sliced_report_definitions_count = count($sliced_report_definitions);
        $requests = array();
        foreach ($sliced_report_definitions as $report_definition) {
            $requests[] = $this->buildRequest($report_definition);
            $offset++;
        }

        $this->unlock($sliced_report_definitions_count);

        return array($sliced_report_definitions_count, $requests);
    }

    /**
     * @param $reports
     * @param ReportDefinition[] $report_definitions
     */
    private function fixFieldTypes($reports, $report_definitions) {
        foreach ($reports as $i => $report) {
            $report_definition = $report_definitions[$i];

            foreach ($report as $stats) {
                foreach ($stats as $field => &$value) {

                    if (isset($report_definition->field_types[$field])) {
                        switch ($report_definition->field_types[$field]) {
                            case 'int' :
                                $value = (int) $value;
                                break;

                            case 'float' :
                                $value = (float) str_replace(',', '', $value);
                                break;

//                            case 'string' :
//                                break;
                        }
                    } else {
                        throw new FieldTypeNotDefinedException();
                    }
                }
            }
        }
    }

}