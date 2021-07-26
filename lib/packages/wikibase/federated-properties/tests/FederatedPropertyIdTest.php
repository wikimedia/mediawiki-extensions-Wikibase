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
		$id = new FederatedPropertyId( $serialization, 'P31' );
		$this->assertEquals( $serialization, $id->serialize() );
		$this->assertEquals( $serialization, $id->getSerialization() );
	}

	public function testCreationFailsWhenSerializationNotURI() {
		$serialization = 'P31';
		$this->expectException( InvalidArgumentException::class );
		$id = new FederatedPropertyId( $serialization, 'P31' );
	}

	public function testCreationFailsIfURIhasNoPath() {
		$serialization = 'http://www.wikidata.org';
		$this->expectException( InvalidArgumentException::class );
		$id = new FederatedPropertyId( $serialization, 'P666' );
	}

	public function testUnserializationWithValidSerialization() {
		$serialization = 'http://www.wikidata.org/entity/P32';
		$id = new FederatedPropertyId( 'http://www.wikidata.org/entity/P31', 'P31' );
		$id->unserialize( $serialization );
		$this->assertEquals( $serialization, $id->getSerialization() );
	}

	/**
	 * @dataProvider remoteIdProvider
	 */
	public function testGetSerializationWithoutConceptBaseUriPrefix( string $fullSerialization, string $pid ) {
		$id = new FederatedPropertyId( $fullSerialization, $pid );

		$this->assertSame( $pid, $id->getRemoteIdSerialization() );
	}

	public function remoteIdProvider() {
		yield [ 'http://www.wikidata.org/entity/P31', 'P31' ];
		yield [ 'http://www.wikidata.org/w/index.php?title=Property:P279', 'P279' ];
	}
}
