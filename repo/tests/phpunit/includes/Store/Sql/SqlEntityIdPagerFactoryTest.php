<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Edrsf\EntityNamespaceLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlEntityIdPagerFactoryTest extends PHPUnit_Framework_TestCase {

	public function testNewSqlEntityIdPager() {
		$factory = new SqlEntityIdPagerFactory(
			new EntityNamespaceLookup( [] ),
			$this->getMock( EntityIdParser::class )
		);
		$pager = $factory->newSqlEntityIdPager();

		$this->assertInstanceOf( SqlEntityIdPager::class, $pager );
	}

}
