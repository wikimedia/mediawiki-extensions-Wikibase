<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlIdGenerator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlIdGeneratorTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	public function testGetNewId_noReservedIds() {
		$generator = new SqlIdGenerator( $this->getRepoDomainDb() );

		$id = $generator->getNewId( 'wikibase-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testReservedIds() {
		$generator = new SqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-reserved' );
		$this->assertSame( 3, $id );
	}

	public function testReservedIds_onlyAppliesForSpecifiedEntityType() {
		$generator = new SqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-non-reserved' );
		$this->assertSame( 1, $id );
	}
}
