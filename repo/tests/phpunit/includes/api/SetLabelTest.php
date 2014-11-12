<?php

namespace Wikibase\Test\Api;

/**
 * @covers Wikibase\Api\SetLabel
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetLabelTest
 * @group LanguageAttributeTest
 * @group BreakingTheSlownessBarrier
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SetLabelTest extends ModifyTermTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		self::$testAction = 'wbsetlabel';

		if( !isset( self::$hasSetup ) ){
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetLabel( $params, $expected ){
		self::doTestSetTerm( 'labels' ,$params, $expected );
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetLabelExceptions( $params, $expected ){
		self::doTestSetTermExceptions( $params, $expected );
	}
}
