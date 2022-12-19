<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataAccessSettingsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$mockSizeInKb = 42;

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'maxSerializedEntitySize' => $mockSizeInKb,
			] )
		);

		$dataAccessSettings = $this->getService( 'WikibaseClient.DataAccessSettings' );

		$this->assertInstanceOf(
			DataAccessSettings::class,
			$dataAccessSettings
		);

		$this->assertSame(
			$mockSizeInKb * 1024,
			$dataAccessSettings->maxSerializedEntitySizeInBytes()
		);
	}

}
