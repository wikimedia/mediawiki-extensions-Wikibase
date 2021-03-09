<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'maxSerializedEntitySize' => 2, // in KiB
			] ) );

		/** @var DataAccessSettings $dataAccessSettings */
		$dataAccessSettings = $this->getService( 'WikibaseRepo.DataAccessSettings' );

		$this->assertInstanceOf( DataAccessSettings::class, $dataAccessSettings );
		$this->assertSame( 2048, $dataAccessSettings->maxSerializedEntitySizeInBytes() );
	}

}
