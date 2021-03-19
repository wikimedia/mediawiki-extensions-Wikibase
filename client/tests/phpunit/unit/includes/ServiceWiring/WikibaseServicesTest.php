<?php

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseServicesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				[ new EntitySource(
					'item',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 0, 'slot' => 'main' ] ],
					'https://item.test/entity/',
					'',
					'',
					'item'
				) ],
				new EntityTypeDefinitions( [] )
			) );
		$this->mockService(
			'WikibaseClient.SingleEntitySourceServicesFactory',
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertInstanceOf(
			WikibaseServices::class,
			$this->getService( 'WikibaseClient.WikibaseServices' )
		);
	}
}
