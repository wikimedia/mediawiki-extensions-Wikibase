<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\ValueValidatorFactory;

/**
 * @covers Wikibase\Repo\ValueValidatorFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ValueValidatorFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getValidatorIdsProvider
	 */
	public function testGetValidatorIds( $valueValidators, $expected ) {
		$valueValidatorFactory = new ValueValidatorFactory( $valueValidators );

		$returnValue = $valueValidatorFactory->getValidatorIds();

		$this->assertEquals( $expected, $returnValue );
	}

	public function getValidatorIdsProvider() {
		return array(
			array(
				array( 'key' => 'value' ),
				array( 'key' )
			)
		);
	}
}
