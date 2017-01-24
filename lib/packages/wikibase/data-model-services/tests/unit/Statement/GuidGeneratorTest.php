<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;

/**
 * @covers Wikibase\DataModel\Services\Statement\GuidGenerator
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GuidGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testGetGuid( EntityId $id ) {
		$guidGen = new GuidGenerator();

		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
	}

	public function entityIdProvider() {
		$argLists = [];

		$argLists[] = [ new ItemId( 'Q123' ) ];
		$argLists[] = [ new ItemId( 'Q1' ) ];
		$argLists[] = [ new PropertyId( 'P31337' ) ];

		return $argLists;
	}

	private function assertIsGuidForId( $guid, EntityId $id ) {
		$this->assertInternalType( 'string', $guid );
		$this->assertStringStartsWith( $id->getSerialization(), $guid );
	}

}
