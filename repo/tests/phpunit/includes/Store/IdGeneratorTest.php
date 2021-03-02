<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlIdGenerator
 * @covers \Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class IdGeneratorTest extends MediaWikiIntegrationTestCase {

	public function testGetNewId() {
		$generator = WikibaseRepo::getIdGenerator();
		/**
		 * @var IdGenerator $clone
		 */
		$clone = clone $generator;

		$id = $generator->getNewId( 'foo' );

		$this->assertIsInt( $id );

		$id1 = $generator->getNewId( 'foo' );

		$this->assertIsInt( $id1 );
		$this->assertNotEquals( $id, $id1 );

		$id2 = $generator->getNewId( 'bar' );
		$this->assertIsInt( $id2 );

		$id3 = $clone->getNewId( 'foo' );

		$this->assertIsInt( $id3 );

		$this->assertTrue( !in_array( $id3, [ $id, $id1 ], true ) );
	}

}
