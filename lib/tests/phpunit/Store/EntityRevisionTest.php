<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers Wikibase\Lib\Store\EntityRevision
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class EntityRevisionTest extends PHPUnit_Framework_TestCase {

	public function testMinimalConstructorArguments() {
		$entity = new Item();
		$revision = new EntityRevision( $entity );

		$this->assertSame( $entity, $revision->getEntity() );
		$this->assertSame( 0, $revision->getRevisionId() );
		$this->assertSame( '', $revision->getTimestamp() );
	}

	public function testAllConstructorArguments() {
		$entity = new Item();
		$revision = new EntityRevision( $entity, 42, '20150211000000' );

		$this->assertSame( $entity, $revision->getEntity() );
		$this->assertSame( 42, $revision->getRevisionId() );
		$this->assertSame( '20150211000000', $revision->getTimestamp() );
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testInvalidConstructorArguments( $revisionId, $mwTimestamp ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new EntityRevision( new Item(), $revisionId, $mwTimestamp );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ null, '20150211000000' ],
			[ '1', '20150211000000' ],
			[ -1, '20150211000000' ],
			[ 1, null ],
			[ 1, 1423612800 ],
			[ 1, '20150211' ],
			[ 1, "20150211000000\n" ],
			[ 1, '2015-02-110000' ],
		];
	}

}
