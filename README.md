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

If you wish to generate a DELTA_LAUNCH_ID to pass it to several types of tests, please check this [website](https://delta-reporter.github.io/delta-reporter/jenkins/)

### Sending media to Delta Reporter ###

You can sent images and video to Delta Reporter, these will be displayed in a container into your test

```
use DeltaReporter\Service\DeltaReporterHTTPService;


abstract class DemoClass extends TestCase
{

	private function demoFunction()
	{
    DeltaReporterHTTPService::saveFileForTest($path, 'img', 'Screenshot description');
  }
}
```

The function `saveFileForTest()` requires three parameters:

- FilePath: string = Full path to upload the media file
- Type: string = Type of media, it accept two values 'img' or 'video'
- Description: string = Description of the media file, which is going to be displayed in a container
