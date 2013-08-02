<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * @covers Wikibase\Lib\ClaimGuidGenerator
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimGuidGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testGetGuid( EntityId $id ) {
		$guidGen = new ClaimGuidGenerator( $id );

		$this->assertIsGuidForId( $guidGen->newGuid(), $id );
		$this->assertIsGuidForId( $guidGen->newGuid(), $id );
		$this->assertIsGuidForId( $guidGen->newGuid(), $id );
	}

	public function entityIdProvider() {
		$argLists = array();

		$argLists[] = array( new ItemId( 'Q123' ) );
		$argLists[] = array( new ItemId( 'Q1' ) );
		$argLists[] = array( new PropertyId( 'P31337' ) );

		return $argLists;
	}

	protected function assertIsGuidForId( $guid, EntityId $id ) {
		$this->assertInternalType( 'string', $guid );
		$this->assertStringStartsWith( $id->getSerialization(), $guid );
	}

}
