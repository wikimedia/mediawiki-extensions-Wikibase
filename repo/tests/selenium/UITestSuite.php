<?php
require_once 'SeleniumTestSuite.php';

class UITestSuite extends SeleniumTestSuite
{
	public function addTests() {
		$testFiles = array(
			'LabelAndDescriptionUITest.php'
		);
		parent::addTestFiles( $testFiles );
	}
}
