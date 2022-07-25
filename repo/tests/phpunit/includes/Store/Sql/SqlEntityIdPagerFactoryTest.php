<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SqlEntityIdPagerFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testNewSqlEntityIdPager() {
		$factory = new SqlEntityIdPagerFactory(
			new EntityNamespaceLookup( [] ),
			$this->getMockEntityIdLookup(),
			$this->createMock( RepoDomainDb::class )
		);
		$pager = $factory->newSqlEntityIdPager();

		$this->assertInstanceOf( SqlEntityIdPager::class, $pager );
	}

	/**
	 * @return EntityIdLookup
	 */
	private function getMockEntityIdLookup() {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );

		$entityIdLookup->method( 'getEntityIdForTitle' )
			->willReturnCallback( function ( Title $title ) {
				return new ItemId( $title->getText() );
			} );

		return $entityIdLookup;
	}

}
