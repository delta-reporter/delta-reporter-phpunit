# Delta Reporter PHPUnit Service #

This service is intended to send information from PHPUnit tests to Delta Reporter

### Installation ###

Installing this service is simple as adding it as a dependency to composer


```json
  "minimum-stability": "dev",
  "require-dev": {
    "delta-reporter/phpunit-client" : "*"
  },
```

### Configuration ###
#### Add listener to phpunit.xml ####


```xml
    <listeners>
        <listener class="DeltaReporter\PHPUnitService" file="vendor/delta-reporter/phpunit-client/src/PHPUnitService.php">
            <arguments>
                <string>HOST URL</string>
                <string>PROJECT NAME</string>
                <string>TEST TYPE</string>
                <boolean>ENABLED</boolean>
            </arguments>
        </listener>
    </listeners>
```

If the environment variable `DELTA_LAUNCH_ID` is not present, a new launch is going to be created on Delta Reporter automatically using the current date as `PHPUnit Launch {Y-m-d\TH:i:s}`
