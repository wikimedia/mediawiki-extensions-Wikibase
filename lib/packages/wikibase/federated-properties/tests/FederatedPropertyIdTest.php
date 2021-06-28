<?php
declare( strict_types=1 );

namespace Wikibase\Lib\Tests\FederatedProperties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;

/**
 * @covers \Wikibase\Lib\FederatedProperties\FederatedPropertyId
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class FederatedPropertyIdTest extends TestCase {

	public function testCreateAndSerializeId() {
		$serialization = 'http://www.wikidata.org/entity/P31';
		$id = new FederatedPropertyId( $serialization );
		$this->assertEquals( $serialization, $id->serialize() );
		$this->assertEquals( $serialization, $id->getSerialization() );
	}

	public function testCreationFailsWhenSerializationNotURI() {
		$serialization = 'P31';
		$this->expectException( InvalidArgumentException::class );
		$id = new FederatedPropertyId( $serialization );
	}

	public function testCreationFailsIfURIhasNoPath() {
		$serialization = 'http://www.wikidata.org';
		$this->expectException( InvalidArgumentException::class );
		$id = new FederatedPropertyId( $serialization );
	}

	public function testUnserializationWithValidSerialization() {
		$serialization = 'http://www.wikidata.org/entity/P32';
		$id = new FederatedPropertyId( 'http://www.wikidata.org/entity/P31' );
		$id->unserialize( $serialization );
		$this->assertEquals( $serialization, $id->getSerialization() );
	}
}
