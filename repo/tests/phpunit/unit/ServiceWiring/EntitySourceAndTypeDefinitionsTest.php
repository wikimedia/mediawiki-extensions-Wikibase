<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceAndTypeDefinitionsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.Settings', new SettingsArray( [ 'federatedPropertiesEnabled' => false ] ) );
		$entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
		$entitySourceDefinitions->method( 'getSources' )->willReturn( [
			new DatabaseEntitySource(
				'test',
				false,
				[],
				'',
				'',
				'',
				''
			),
		] );

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$entitySourceDefinitions
		);

		$this->assertInstanceOf(
			EntitySourceAndTypeDefinitions::class,
			$this->getService( 'WikibaseRepo.EntitySourceAndTypeDefinitions' )
		);
	}

}
