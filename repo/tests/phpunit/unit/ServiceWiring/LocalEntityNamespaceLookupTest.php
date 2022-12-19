<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalEntityNamespaceLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$nsIds = [
			'item' => 666,
			'property' => 777,
		];

		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			new DatabaseEntitySource(
				'local',
				false,
				array_map( function ( $nsId ) {
					return [
						'namespaceId' => $nsId,
						'slot' => SlotRecord::MAIN,
					];
				}, $nsIds ),
				'http://www.example.com/entity',
				'wd',
				'wdt',
				'localwiki'
			) );

		$entityNSLookup = $this->getService( 'WikibaseRepo.LocalEntityNamespaceLookup' );

		$this->assertInstanceOf( EntityNamespaceLookup::class, $entityNSLookup );
		$this->assertEquals( $nsIds, $entityNSLookup->getEntityNamespaces() );
	}

}
