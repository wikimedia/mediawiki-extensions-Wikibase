<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemAndPropertySourceTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				[
					new EntitySource(
						'someSourceName',
						'repodb',
						[ 'item' => [ 'namespaceId' => 123, 'slot' => 'main' ] ],
						'',
						'',
						'',
						'repo'
					),
					new EntitySource(
						'someOtherSourceName',
						'otherdb',
						[ 'property' => [ 'namespaceId' => 321, 'slot' => 'main' ] ],
						'',
						'',
						'',
						'other'
					)
				],
				new EntityTypeDefinitions( [] )
			)
		);
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray(
				[ 'itemAndPropertySourceName' => 'someSourceName' ]
			)
		);

		$itemAndPropertySourceOfLocalRepo = $this->getService( 'WikibaseClient.ItemAndPropertySource' );
		$this->assertInstanceOf(
			EntitySource::class,
			$itemAndPropertySourceOfLocalRepo
		);
		$this->assertEquals( 'repodb', $itemAndPropertySourceOfLocalRepo->getDatabaseName() );
	}
}
