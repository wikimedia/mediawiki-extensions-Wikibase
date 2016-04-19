<?php

namespace Wikibase\Repo\Tests\Api;

/**
 * @covers Wikibase\Repo\Api\SetDescription
 * @covers Wikibase\Repo\Api\ModifyTerm
 * @covers Wikibase\Repo\Api\ModifyEntity
 *
 * @group Database
 * @group medium
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetDescriptionTest
 * @group LanguageAttributeTest
 * @group BreakingTheSlownessBarrier
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SetDescriptionTest extends ModifyTermTestCase {

	private static $hasSetup;

	protected function setUp() {
		parent::setUp();

		self::$testAction = 'wbsetdescription';

		if ( !isset( self::$hasSetup ) ) {
			$this->initTestEntities( array( 'Empty' ) );
		}
		self::$hasSetup = true;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testSetDescription( $params, $expected ) {
		self::doTestSetTerm( 'descriptions', $params, $expected );
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testSetDescriptionExceptions( $params, $expected ) {
		self::doTestSetTermExceptions( $params, $expected );
	}

}
