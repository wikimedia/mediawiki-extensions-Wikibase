<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDataFormatProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$mockEntityDataFormats = [
			'test-format',
			'json',
		];

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [
				'entityDataFormats' => $mockEntityDataFormats,
			] )
		);

		/** @var EntityDataFormatProvider $entityDataFormatProvider */
		$entityDataFormatProvider = $this->getService( 'WikibaseRepo.EntityDataFormatProvider' );

		$this->assertInstanceOf(
			EntityDataFormatProvider::class,
			$entityDataFormatProvider
		);

		$this->assertSame(
			$mockEntityDataFormats,
			$entityDataFormatProvider->getAllowedFormats()
		);
	}

}
