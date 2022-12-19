<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;
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
			new DatabaseEntitySource(
				'other',
				'other',
				[],
				'http://example.com/entity/',
				'ot',
				'ott',
				'otherwiki'
			),
			new DatabaseEntitySource(
				'local',
				false,
				[],
				'http://example.com/entity/',
				'wd',
				'wdt',
				'localwiki'
			),
		];
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( $settingsArray ) );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $mockEntitySources, new SubEntityTypesMapper( [] ) ) );

		$localEntitySource = $this->getService( 'WikibaseRepo.LocalEntitySource' );

		$this->assertInstanceOf( DatabaseEntitySource::class, $localEntitySource );
		$this->assertSame( $mockEntitySources[1], $localEntitySource );
	}

}
