<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeChangeOpSerializationValidatorTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'badgeItems' => [
					'Q123' => 'wb-badge-one-two-three',
				],
			] ) );

		$this->assertInstanceOf(
			SiteLinkBadgeChangeOpSerializationValidator::class,
			$this->getService( 'WikibaseRepo.SiteLinkBadgeChangeOpSerializationValidator' )
		);
	}

}
