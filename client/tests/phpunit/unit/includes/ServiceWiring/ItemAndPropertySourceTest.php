<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;

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
					new DatabaseEntitySource(
						'someSourceName',
						'repodb',
						[ 'item' => [ 'namespaceId' => 123, 'slot' => SlotRecord::MAIN ] ],
						'',
						'',
						'',
						'repo'
					),
					new DatabaseEntitySource(
						'someOtherSourceName',
						'otherdb',
						[ 'property' => [ 'namespaceId' => 321, 'slot' => SlotRecord::MAIN ] ],
						'',
						'',
						'',
						'other'
					),
				],
				new SubEntityTypesMapper( [] )
			)
		);
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray(
				[ 'itemAndPropertySourceName' => 'someSourceName' ]
			)
		);

		$itemAndPropertySourceOfLocalRepo = $this->getService( 'WikibaseClient.ItemAndPropertySource' );
		$this->assertInstanceOf(
			DatabaseEntitySource::class,
			$itemAndPropertySourceOfLocalRepo
		);
		$this->assertEquals( 'repodb', $itemAndPropertySourceOfLocalRepo->getDatabaseName() );
	}
}
