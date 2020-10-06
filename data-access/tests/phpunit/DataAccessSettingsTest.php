<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataModel\Entity\Int32EntityId;

/**
 * @covers \Wikibase\DataAccess\DataAccessSettings
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends \PHPUnit\Framework\TestCase {

	public function testConvertsMaxSerializedEntitySizeFromKiloBytesToBytes() {
		$settings = new DataAccessSettings(
			1
		);

		$this->assertEquals( 1024, $settings->maxSerializedEntitySizeInBytes() );
	}

	public function testNormalizedPropertyTerms() {
		$settings = new DataAccessSettings(
			1
		);

		$this->assertTrue( $settings->useNormalizedPropertyTerms() );
	}

	public function testUseNormalizedItemTerms() {
		$settings = new DataAccessSettings(
			1
		);

		$this->assertTrue( $settings->useNormalizedItemTerms( 1 ) );
		$this->assertTrue( $settings->useNormalizedItemTerms( Int32EntityId::MAX ) );
	}

}
