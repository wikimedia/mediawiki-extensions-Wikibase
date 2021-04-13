<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDataUriManagerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityDataFormatProvider = $this->createMock( EntityDataFormatProvider::class );
		$entityDataFormatProvider->expects( $this->once() )
			->method( 'getSupportedFormats' )
			->willReturn( [ 'myformat' ] );
		$entityDataFormatProvider->expects( $this->once() )
			->method( 'getExtension' )
			->with( 'myformat' )
			->willReturn( 'mft' );
		$this->mockService( 'WikibaseRepo.EntityDataFormatProvider',
			$entityDataFormatProvider );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'entityDataCachePaths' => [],
			] ) );
		$this->mockService( 'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class ) );

		/** @var EntityDataUriManager $entityDataUriManager */
		$entityDataUriManager = $this->getService( 'WikibaseRepo.EntityDataUriManager' );

		$this->assertInstanceOf( EntityDataUriManager::class, $entityDataUriManager );
		$this->assertSame( 'html', $entityDataUriManager->getExtension( 'html' ) );
		$this->assertSame( 'mft', $entityDataUriManager->getExtension( 'myformat' ) );
	}

}
