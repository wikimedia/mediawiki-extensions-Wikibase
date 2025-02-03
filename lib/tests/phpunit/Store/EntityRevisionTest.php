<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @covers \Wikibase\Lib\Store\EntityRevision
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityRevisionTest extends \PHPUnit\Framework\TestCase {

	public function testMinimalConstructorArguments() {
		$entity = new Item();
		$revision = new EntityRevision( $entity );

		$this->assertSame( $entity, $revision->getEntity() );
		$this->assertSame( EntityRevision::UNSAVED_REVISION, $revision->getRevisionId() );
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
		$this->expectException( InvalidArgumentException::class );
		new EntityRevision( new Item(), $revisionId, $mwTimestamp );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ -1, '20150211000000' ],
			[ 1, '20150211' ],
			[ 1, "20150211000000\n" ],
			[ 1, '2015-02-110000' ],
		];
	}

}
