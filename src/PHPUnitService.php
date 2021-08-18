<?php

namespace DeltaReporter;

use PHPUnit\Framework as Framework;
use DeltaReporter\Service\DeltaReporterHTTPService as DeltaReporterHTTPService;
use GuzzleHttp\Psr7\Response as Response;

class PHPUnitService implements Framework\TestListener
{

    private $projectName;
    private $host;
    private $testType;
    private $launchID;
    private $className;
    private $testName;

    private $testSuiteStatus;
    private $testRunStatus;

    private static $testSuiteCounter = 0;
    private static $testSuitePath;

    /**
     * @var DeltaReporterHTTPService
     */
    protected static $httpService;

    /**
     * PHPUnitService constructor.
     * @param $host
     * @param $projectName
     * @param $testType
     */
    public function __construct(string $host, string $projectName, string $testType, bool $enabled)
    {
        $this->enabled = $enabled;
        if (!$this->enabled) {
            return;
        }
        $this->host = $host;
        $this->projectName = $projectName;
        $this->testType = $testType;
        $this->launchID = getenv('DELTA_LAUNCH_ID');

        $this->configureClient();
        if (!$this->launchID) {
            self::$httpService->createTestLaunch("PHPUnit Launch " . date('Y-m-d\TH:i:s'));
        } else {
            self::$httpService->setTestLaunchID($this->launchID);
        }
        $this->testRunStatus = "Passed";
        self::$httpService->createTestRun($this->testType);
    }

    /**
     * agentPHPUnit destructor.
     */
    public function __destruct()
    {
        if (!$this->enabled) {
            return;
        }
        $status = self::getStatusByBool(true);
        $HTTPResult = self::$httpService->updateTestRun($this->testRunStatus);
    }

    /**
     * Configure http client.
     */
    private function configureClient()
    {
        if (!$this->enabled) {
            return;
        }
        $baseURI = sprintf($this->host);
        DeltaReporterHTTPService::configureClient($baseURI, $this->host, $this->projectName);
        self::$httpService = new DeltaReporterHTTPService();
    }

    /**
     * @param bool $isFailedItem
     * @return string
     */
    private static function getStatusByBool(bool $isFailedItem)
    {
        if ($isFailedItem) {
            $stringItemStatus = 'FAILED';
        } else {
            $stringItemStatus = 'PASSED';
        }
        return $stringItemStatus;
    }

    /**
     * Is a suite without name
     *
     * @param Framework\TestSuite $suite
     * @return bool
     */
    private static function isNoNameSuite(\PHPUnit\Framework\TestSuite $suite):bool
    {
        return $suite->getName() !== "";
    }

    /**
     * A warning occurred.
     * @param Framework\Test $test
     * @param Framework\Warning $e
     * @param float $time
     */
    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        // TODO: Implement addWarning() method.
    }

    /**
     * Risky test.
     * @param Framework\Test $test
     * @param Throwable $t
     * @param float $time
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addRiskyTest() method.
    }

    /**
     * An error occurred.
     * @param Framework\Test $test
     * @param Throwable $t
     * @param float $time
     */
    public function addError(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        // TODO: Implement addError() method.
        if (!$this->enabled) {
            return;
        }
        $errorMessage = 'ERROR: ' . $t;
        $trace = $t;
        $fileAndLine = "ERROR";
        $errorType = "ERROR";
        $testStatus = 'Failed';

        self::$httpService->updateTestHistory($testStatus, $trace, $fileAndLine, $errorMessage, $errorType);
    }

    /**
     * A test ended.
     * @param Framework\Test $test
     * @param float $time
     */
    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        if (!$this->enabled) {
            return;
        }
        if (!$test->getStatus()) {
            self::$httpService->updateTestHistory('Passed', '', '', '', '');
        }
    }

    /**
     * A test started.
     * @param Framework\Test $test
     */
    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->testName = $test->getName();

        $response = self::$httpService->createTestHistory($this->testName);
    }

    /**
     * A test suite started.
     * @param Framework\TestSuite $suite
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if (!$this->enabled) {
            return;
        }
        if (self::isNoNameSuite($suite)) {
                self::$testSuiteCounter++;
                if (self::$testSuiteCounter == 1) {
                    self::$testSuitePath = str_replace(getcwd(),"", $suite->getName());
                } elseif (self::$testSuiteCounter == 2) {
                    $this->testSuiteStatus = "Successful";
                    $suiteName = self::$testSuitePath . ":" . $suite->getName();
                    $response = self::$httpService->createTestSuiteHistory($suiteName, $this->testType);
                }
        }
    }

    /**
     * A test suite ended.
     * @param Framework\TestSuite $suite
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if (!$this->enabled) {
            return;
        }
        if (self::isNoNameSuite($suite)) {
            self::$testSuiteCounter--;
            if (self::$testSuiteCounter == 1) {
                self::$httpService->updateTestSuiteHistory($this->testSuiteStatus);
            }
        }
    }

    /**
     * A failure occurred.
     * @param Framework\Test $test
     * @param Framework\AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        if (!$this->enabled) {
            return;
        }
        $errorMessage = $e->toString();
        $trace = $e->getTraceAsString();
        $className = get_class($test);
        $traceArray = $e->getTrace();
        $arraySize = sizeof($traceArray);
        $foundedFirstMatch = false;
        $counter = 0;
        $fileAndLine = "";
        $errorType = "";
        $fullTrace = "";
        while (!$foundedFirstMatch and $counter < $arraySize) {
            if (strpos($traceArray[$counter]["file"], $className) != false) {
                $fileName = $traceArray[$counter]["file"];
                $fileLine = $traceArray[$counter]["line"];
                $function = $traceArray[$counter]["function"];
                $assertClass = $traceArray[$counter]["class"];
                $type = $traceArray[$counter]["type"];
                $args = implode(',', $traceArray[$counter]["args"]);
                $fileAndLine = $fileAndLine . "\n" . $fileName . ':' . $fileLine;
                $errorType = $errorType . "\n" . $assertClass . $type . $function . '(' . $args . ')';
                $foundedFirstMatch = true;
            }
            $counter++;
        }
        $testStatus = 'Failed';
        $this->testSuiteStatus = "Failed";
        $this->testRunStatus = "Failed";

        self::$httpService->updateTestHistory($testStatus, $trace, $fileAndLine, $errorMessage, $errorType);
    }

    /**
     * Skipped test.
     * @param Framework\Test $test
     * @param Throwable $t
     * @param float $time
     */
    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        if (!$this->enabled) {
            return;
        }
        $errorMessage = $t->toString();
        $trace = $t->getTraceAsString();
        $className = get_class($test);
        $traceArray = $t->getTrace();
        $arraySize = sizeof($traceArray);
        $foundedFirstMatch = false;
        $counter = 0;
        $fileAndLine = "";
        $errorType = "";
        $fullTrace = "";
        while (!$foundedFirstMatch and $counter < $arraySize) {
            if (strpos($traceArray[$counter]["file"], $className) != false) {
                $fileName = $traceArray[$counter]["file"];
                $fileLine = $traceArray[$counter]["line"];
                $function = $traceArray[$counter]["function"];
                $assertClass = $traceArray[$counter]["class"];
                $type = $traceArray[$counter]["type"];
                $args = implode(',', $traceArray[$counter]["args"]);
                $fileAndLine = $fileAndLine . "\n" . $fileName . ':' . $fileLine;
                $errorType = $errorType . "\n" . $assertClass . $type . $function . '(' . $args . ')';
                $foundedFirstMatch = true;
            }
            $counter++;
        }
        $testStatus = 'Skipped';

        self::$httpService->updateTestHistory($testStatus, $trace, $fileAndLine, $errorMessage, $errorType);
    }

    /**
     * Incomplete test.
     * @param Framework\Test $test
     * @param Throwable $t
     * @param float $time
     */
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Throwable $t, float $time): void
    {
        if (!$this->enabled) {
            return;
        }
        $errorMessage = $t->toString();
        $trace = $t->getTraceAsString();
        $className = get_class($test);
        $traceArray = $t->getTrace();
        $arraySize = sizeof($traceArray);
        $foundedFirstMatch = false;
        $counter = 0;
        $fileAndLine = "";
        $errorType = "";
        $fullTrace = "";
        while (!$foundedFirstMatch and $counter < $arraySize) {
            if (strpos($traceArray[$counter]["file"], $className) != false) {
                $fileName = $traceArray[$counter]["file"];
                $fileLine = $traceArray[$counter]["line"];
                $function = $traceArray[$counter]["function"];
                $assertClass = $traceArray[$counter]["class"];
                $type = $traceArray[$counter]["type"];
                $args = implode(',', $traceArray[$counter]["args"]);
                $fileAndLine = $fileAndLine . "\n" . $fileName . ':' . $fileLine;
                $errorType = $errorType . "\n" . $assertClass . $type . $function . '(' . $args . ')';
                $foundedFirstMatch = true;
            }
            $counter++;
        }
        $testStatus = 'Incomplete';

        self::$httpService->updateTestHistory($testStatus, $trace, $fileAndLine, $errorMessage, $errorType);
    }
}
