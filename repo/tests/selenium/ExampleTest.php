<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase/SauceOnDemandTestCase.php';


/**
 * // The following line makes the class 'ExampleTest' run its tests in parallel.
 * // PHP can read it even though it lives in a comment.
 * // Read more at http://blog.fedecarg.com/2008/07/19/using-annotations-in-php/
 * 
 * @runTestsInParallel 10
 */
class ExampleTest extends PHPUnit_Extensions_SeleniumTestCase_SauceOnDemandTestCase {
    public static $browsers = array(
      array(
        'name' => 'Sauce Ondemand PHPUnit example (FF 7)',
        'browser' => 'firefox',
        'os' => 'Windows 2003',
        'browserVersion' => '7',
      ),
      array(
        'name' => 'Sauce Ondemand PHPUnit example (IE 9)',
        'browser' => 'iexplore',
        'os' => 'Windows 2008',
        'browserVersion' => '9',
      ),
      array(
        'name' => 'Sauce Ondemand PHPUnit example (Safari 4)',
        'browser' => 'safari',
        'os' => 'Windows 2003',
        'browserVersion' => '4',
      )
    );

    function setUp() {
        $this->setBrowserUrl("http://saucelabs.com");
    }

    function test_example() {
        $this->open('/test/guinea-pig');
        $this->waitForTitle('I am a page title - Sauce Labs');
        $this->type('comments', 'Hello! I am some example comments. I should appear in the page after you submit the form');
        $this->click('submit');
        $this->waitForTextPresent('Your comments: Hello! I am some example comments. I should appear in the page after you submit the form');
        $this->assertTextNotPresent('I am some other page content');
        $this->click('link=i am a link');
        $this->waitForTextPresent('I am some other page content');
    }
}
