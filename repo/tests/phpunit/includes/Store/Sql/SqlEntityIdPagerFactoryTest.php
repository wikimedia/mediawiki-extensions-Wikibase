<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory
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
			new ItemIdParser()
		);
		$pager = $factory->newSqlEntityIdPager();

		$this->assertInstanceOf( SqlEntityIdPager::class, $pager );
	}

}
