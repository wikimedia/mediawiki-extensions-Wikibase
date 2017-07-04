<?php

namespace Wikibase\Repo\Tests\Store;

use Wikibase\IdGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class IdGeneratorTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = [ WikibaseRepo::getDefaultInstance()->getStore()->newIdGenerator() ];

		return [ $instances ];
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetNewId( IdGenerator $generator ) {
		/**
		 * @var IdGenerator $clone
		 */
		$clone = clone $generator;

		$id = $generator->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id );

		$id1 = $generator->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id1 );
		$this->assertNotEquals( $id, $id1 );

		$id2 = $generator->getNewId( 'bar' );
		$this->assertInternalType( 'integer', $id2 );

		$id3 = $clone->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id3 );

		$this->assertTrue( !in_array( $id3, [ $id, $id1 ], true ) );
	}

}
