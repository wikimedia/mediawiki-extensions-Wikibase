<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\InvalidArgumentException;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalEntitySourceTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$settingsArray = [ 'localEntitySourceName' => 'local' ];
		$mockEntitySources = [
			new EntitySource(
				'other',
				'other',
				[],
				'http://example.com/entity/',
				'ot',
				'ott',
				'otherwiki'
			),
			new EntitySource(
				'local',
				false,
				[],
				'http://example.com/entity/',
				'wd',
				'wdt',
				'localwiki'
			)
		];

		$this->serviceContainer
			->method( 'get' )
			->willReturnCallback( function ( string $id ) use ( $settingsArray, $mockEntitySources ) {
				switch ( $id ) {
					case 'WikibaseRepo.Settings':
						return new SettingsArray( $settingsArray );
					case 'WikibaseRepo.EntitySourceDefinitions':
						return new EntitySourceDefinitions( $mockEntitySources, new EntityTypeDefinitions( [] ) );
					default:
						throw new InvalidArgumentException( "Unexpected service name: $id" );
				}
			} );

		$localEntitySource = $this->getService( 'WikibaseRepo.LocalEntitySource' );

		$this->assertInstanceOf( EntitySource::class, $localEntitySource );
		$this->assertSame( $mockEntitySources[1], $localEntitySource );
	}

}
