<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use HashBagOStuff;
use MediaWikiTestCase;
use WANObjectCache;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTypeIdsStoreTest extends MediaWikiTestCase {

	/** @var TypesNameTableStore */
	private $typesStore;

	protected function getSchemaOverrides( IMaintainableDatabase $db ) {
		return [
			'scripts' => [
				__DIR__ . '/../../../../../../repo/sql/AddNormalizedTermsTablesDDL.sql',
			],
			'create' => [
				'wbt_type',
			],
		];
	}

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';

		$loadBalancer = $this->createMock( ILoadBalancer::class );
		$loadBalancer->method( 'getConnection' )
			->willReturn( $this->db );
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$this->typesStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$cache
		);
	}

	public function testAcquireTypeIds_sameNamesTwice_returnsSameIds() {
		$ids1 = $this->typesStore->acquireTypeIds( [ 'label', 'description' ] );
		$ids2 = $this->typesStore->acquireTypeIds( [ 'description', 'label' ] );

		ksort( $ids1 );
		ksort( $ids2 );

		$this->assertSame( $ids1, $ids2 );
	}

	public function testAcquireTypeIdsAndResolveTypeIds() {
		$ids = $this->typesStore->acquireTypeIds( [ 'label', 'description' ] );
		$names = $this->typesStore->resolveTypeIds( array_values( $ids ) );

		$this->assertSame( $ids, array_flip( $names ) );
	}

}
