<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;

/**
 * @covers \Wikibase\DataModel\Services\Statement\GuidGenerator
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GuidGeneratorTest extends TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testGetGuid( EntityId $id ) {
		$guidGen = new GuidGenerator();

		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
		$this->assertIsGuidForId( $guidGen->newGuid( $id ), $id );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testGetStatementId( EntityId $id ) {
		$guidGen = new GuidGenerator();

		$this->assertIsGuidForId( (string)$guidGen->newStatementId( $id ), $id );
		$this->assertIsGuidForId( (string)$guidGen->newStatementId( $id ), $id );
		$this->assertIsGuidForId( (string)$guidGen->newStatementId( $id ), $id );
	}

	public function entityIdProvider() {
		$argLists = [];

		$argLists[] = [ new ItemId( 'Q123' ) ];
		$argLists[] = [ new ItemId( 'Q1' ) ];
		$argLists[] = [ new NumericPropertyId( 'P31337' ) ];

		return $argLists;
	}

	private function assertIsGuidForId( $guid, EntityId $id ) {
		$this->assertIsString( $guid );
		$this->assertStringStartsWith( $id->getSerialization(), $guid );
	}

}
