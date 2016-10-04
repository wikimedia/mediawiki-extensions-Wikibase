<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntityIdPagerFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlEntityIdPagerFactoryTest extends MediaWikiTestCase {

	public function testNewSqlEntityIdPager() {
		$factory = new SqlEntityIdPagerFactory( new EntityIdComposer( [] ) );
		$pager = $factory->newSqlEntityIdPager();

		$this->assertInstanceOf( SqlEntityIdPager::class, $pager );
	}

}
