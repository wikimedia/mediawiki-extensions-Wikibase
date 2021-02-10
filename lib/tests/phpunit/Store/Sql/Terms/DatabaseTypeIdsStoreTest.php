<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use HashBagOStuff;
use MediaWikiIntegrationTestCase;
use WANObjectCache;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTypeIdsStoreTest extends MediaWikiIntegrationTestCase {

	/** @var DatabaseTypeIdsStore */
	private $typeIdsStore;

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )->willReturn( $this->db );
		$loadBalancer->method( 'getConnectionRef' )->willReturnCallback(
			function ( $i, $g, $domain, $flg ) use ( $loadBalancer ) {
				return new DBConnRef( $loadBalancer, $this->db, $i );
			}
		);
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$this->typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$cache
		);
	}

	public function testAcquireTypeIds_sameNamesTwice_returnsSameIds() {
		$ids1 = $this->typeIdsStore->acquireTypeIds( [ 'label', 'description' ] );
		$ids2 = $this->typeIdsStore->acquireTypeIds( [ 'description', 'label' ] );

		ksort( $ids1 );
		ksort( $ids2 );

		$this->assertSame( $ids1, $ids2 );
	}

	public function testAcquireTypeIdsAndResolveTypeIds() {
		$ids = $this->typeIdsStore->acquireTypeIds( [ 'label', 'description' ] );
		$names = $this->typeIdsStore->resolveTypeIds( array_values( $ids ) );

		$this->assertSame( $ids, array_flip( $names ) );
	}

	public function testLookupTypeIds() {
		$acquireIds = $this->typeIdsStore->acquireTypeIds( [ 'label', 'description' ] );
		$lookupIds = $this->typeIdsStore->lookupTypeIds( [ 'label', 'description' ] );

		$this->assertSame( $acquireIds, $lookupIds );
	}

	public function testLookupTypeIds_withUnknownTypes_associatesUnknownTypesWithNull() {
		$lookupIds = $this->typeIdsStore->lookupTypeIds( [ 'label' ] );
		$this->assertSame( [ 'label' => null ], $lookupIds );
	}

}
