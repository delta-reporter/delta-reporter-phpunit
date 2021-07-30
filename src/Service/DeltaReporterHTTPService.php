<?php

namespace DeltaReporter\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Delta reporter HTTP service.
 * Provides basic methods to send data to Delta reporter.
 *
 * @author Juan Negrier
 */
class DeltaReporterHTTPService
{

    /**
     *
     * @var string
     */
    const EMPTY_ID = 'empty id';

    /**
     *
     * @var string
     */
    const FORMAT_DATE = 'Y-m-d\TH:i:s';

    /**
     *
     * @var string
     */
    protected static $baseURI;

    /**
     *
     * @var string
     */
    protected static $host;

    /**
     *
     * @var string
     */
    protected static $projectName;

    /**
     *
     * @var string
     */
    protected static $launchID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $testRunID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $testSuiteID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $testSuiteHistoryID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $testHistoryID = self::EMPTY_ID;

    /**
     *
     * @var string
     */
    protected static $testID = self::EMPTY_ID;

    /**
     *
     * @var \GuzzleHttp\Client
     */
    protected static $client;

    function __construct()
    {
        self::$client = new Client([
            'base_uri' => self::$baseURI,
            'http_errors' => false,
            'verify' => false
        ]);
    }
    
    /**
     * @param string $baseURI
     * @param string $host
     * @param string $projectName
     */
    public static function configureClient(string $baseURI, string $host, string $projectName)
    {
        self::$baseURI = $baseURI;
        self::$host = $host;
        self::$projectName = $projectName;
    }
 
    /**
     * Create test launch
     *
     * @param string $launchName
     *            - name of test launch
     * @return ResponseInterface - result of request
     */
    public static function createTestLaunch(string $launchName)
    {
        $result = self::$client->post('api/v1/launch', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'project' => self::$projectName,
                'name' => $launchName
            )
        ));
        $array = json_decode($result->getBody()->getContents());
        self::$launchID = $array->{'id'};
        return $result;
    }

    /**
     * Create test run
     *
     * @param string $testType
     *            - name of the test type to run
     * @return ResponseInterface - result of request
     */
    public static function createTestRun(string $testType)
    {
        $result = self::$client->post('api/v1/test_run', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'test_type' => $testType,
                'launch_id' => self::$launchID,
                'start_datetime' => self::getTime()
            )
        ));
        $array = json_decode($result->getBody()->getContents());
        self::$testRunID = $array->{'id'};
        return $result;
    }

    /**
     * Update test run
     *
     * @param string $runStatus
     *            - status of test run
     * @return ResponseInterface - result of request
     */
    public static function updateTestRun(string $runStatus)
    {
        $result = self::$client->put('api/v1/test_run', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'test_run_id' => self::$testRunID,
                'end_datetime' => self::getTime(),
                'test_run_status' => $runStatus
            )
        ));
        return $result;
    }

    /**
     * Create test suite history
     *
     * @param string $name
     *            - test suite name
     * @param string $test_type
     *            - test type
     * @return ResponseInterface - result of request
     */
    public static function createTestSuiteHistory(string $name, string $test_type)
    {
        $result = self::$client->post('api/v1/test_suite_history', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'name' => $name,
                'test_type' => $test_type,
                'test_run_id' => self::$testRunID,
                'start_datetime' => self::getTime(),
                'project' => self::$projectName
            )
        ));
        $array = json_decode($result->getBody()->getContents());
        self::$testSuiteID = $array->{'test_suite_id'};
        self::$testSuiteHistoryID = $array->{'test_suite_history_id'};
        return $result;
    }

    /**
     * Update test suite history
     *
     * @param string $test_suite_status
     *            - test suite status
     * @return ResponseInterface - result of request
     */
    public static function updateTestSuiteHistory(string $test_suite_status)
    {
        $result = self::$client->put('api/v1/test_suite_history', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'test_suite_history_id' => self::$testSuiteHistoryID,
                'end_datetime' => self::getTime(),
                'test_suite_status' => $test_suite_status
            )
        ));
        return $result;
    }

    /**
     * Get local time
     *
     * @return string with local time
     */
    protected static function getTime()
    {
        return date(self::FORMAT_DATE);
    }

    /**
     * Update test history
     *
     * @param string $testStatus
     *            - test status
     * @param string $trace
     *            - log trace
     * @return ResponseInterface - result of request
     */
    public static function updateTestHistory(string $testStatus, string $trace, string $file, string $message, string $error_type)
    {
        $result = self::$client->put('api/v1/test_history', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'test_history_id' => self::$testHistoryID,
                'end_datetime' => self::getTime(),
                'test_status' => $testStatus,
                'trace' => $trace,
                'file' => $file,
                'message' => $message,
                'error_type' => $error_type,
                'retries' => NULL
            )
        ));
        return $result;
    }

    /**
     * Create test history
     *
     * @param string $name
     *            - test name
     * @return ResponseInterface - result of request
     */
    public static function createTestHistory(string $name)
    {
        $result = self::$client->post('api/v1/test_history', array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'json' => array(
                'name' => $name,
                'start_datetime' => self::getTime(),
                'test_suite_id' => self::$testSuiteID,
                'test_run_id' => self::$testRunID,
                'test_suite_history_id' => self::$testSuiteHistoryID
            )
        ));
        $array = json_decode($result->getBody()->getContents());
        self::$testID = $array->{'test_id'};
        self::$testHistoryID = $array->{'test_history_id'};
        return $result;
    }

    /**
     * Save save file for test
     *
     * @param string $file_path
     *            - file path to collect file
     * @param string $type
     *            - type of file, could be 'img' or 'video'
     * @param string $description
     *            - description of the uploaded file
     * @return ResponseInterface - result of request
     */
    public static function saveFileForTest(string $file_path, string $type, string $description)
    {
        $result = self::$client->post('api/v1/file_receiver_test_history/' . self::$testHistoryID, array(
            'multipart' => [
                [
                    'name'     => 'type',
                    'contents' => $type
                ],
                [
                    'name'     => 'description',
                    'contents' => $description
                ],
                [
                    'name'     => 'file',
                    'contents' => Psr7\Utils::tryFopen($file_path, 'r')
                ],
            ]
        ));
        return $result;
    }
}
