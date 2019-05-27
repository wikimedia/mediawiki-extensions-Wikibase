<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use HashBagOStuff;
use MediaWikiTestCase;
use WANObjectCache;
use Wikibase\Lib\Store\Sql\Terms\SqlTypeIdsStore;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\SqlTypeIdsStore
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SqlTypeIdsStoreTest extends MediaWikiTestCase {

	/** @var SqlTypeIdsStore */
	private $typeIdsStore;

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
		$this->typeIdsStore = new SqlTypeIdsStore(
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

}
