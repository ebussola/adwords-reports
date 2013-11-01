<?php
/**
 * Created by PhpStorm.
 * User: usuario
 * Date: 30/10/13
 * Time: 14:19
 */

namespace ebussola\adwords\reports;

use ebussola\adwords\reports\ReportDefinition;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\MultiTransferException;
use Guzzle\Http\Message\Response;

class Reports {

    const API_VERSION = 'v201309';

    const MAX_PARALLEL_DOWNLOADS = 50;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var XMLParser
     */
    private $xml_parser;

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
     * @param Client $client
     * @param string $auth_token
     * @param string $developer_token
     * @param string $customer_id
     */
    public function __construct(Client $client, XMLParser $xml_parser,
                                $auth_token, $developer_token, $customer_id) {

        $this->client = $client;
        $this->xml_parser = $xml_parser;
        $this->auth_token = $auth_token;
        $this->developer_token = $developer_token;
        $this->customer_id = $customer_id;
    }

    /**
     * @param ReportDefinition[] $report_definitions
     *
     * @return array
     */
    public function downloadReports($report_definitions) {

        $responses = array();
        $offset = 0;
        do {

            /** @var ReportDefinition[] $sliced_report_definitions */
            $sliced_report_definitions = array_slice($report_definitions, $offset, self::MAX_PARALLEL_DOWNLOADS);
            $requests = array();
            foreach ($sliced_report_definitions as $report_definition) {
                $requests[] = $this->buildRequest($report_definition);
                $offset++;
            }

            try {
                $responses = array_merge($responses, $this->client->send($requests));
            } catch (MultiTransferException $e) {
                $messages = '';
                foreach ($e as $exception) {
                    $element = new \SimpleXMLElement($exception->getResponse()->getBody('true'));
                    $api_error = $element->ApiError->type;
                    $trigger = $element->ApiError->trigger;

                    $messages .= $api_error. ' : ' .$trigger . "\n";
                }

                throw new \Exception($messages);
            }

        } while (count($responses) < count($report_definitions));

        $reports = array();
        /** @var Response[] $responses */
        foreach ($responses as $response) {
            $response_body = $response->getBody(true);
            $xml = gzdecode($response_body);
            $report = $this->xml_parser->reportXmlToArray($xml);

            $reports[] = $report;
        }

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
     * @param string            $report_name
     * @param array             $predicates
     * @param \DateTime         $date_start
     * @param \DateTime         $date_end
     * @param \ReportDefinition $report_definition
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
            $report_definition = new \ebussola\adwords\reports\reportdefinition\ReportDefinition(new \ReportDefinition());
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
        $request = $this->client->post('https://adwords.google.com/api/adwords/reportdownload/v201309', array(
            'Authorization'    => $this->auth_token,
            'developerToken'   => $this->developer_token,
            'clientCustomerId' => $this->customer_id
        ), array(
            '__rdxml' => $xml
        ));

        return $request;
    }

}