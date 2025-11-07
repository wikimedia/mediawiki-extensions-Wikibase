<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Federation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Federation\RemoteEntityId;

/**
 * @covers \Wikibase\Repo\Federation\RemoteEntityId
 */
class RemoteEntityIdTest extends TestCase {

	public function testSerializationForItem(): void {
		$local = new ItemId( 'Q42' );
		$id = new RemoteEntityId( 'wikidata', $local );

		$this->assertSame( 'item', $id->getEntityType() );
		$this->assertSame( 'wikidata:Q42', $id->getSerialization() );
	}

	public function testEquals(): void {
		$a = new RemoteEntityId( 'wikidata', new ItemId( 'Q42' ) );
		$b = new RemoteEntityId( 'wikidata', new ItemId( 'Q42' ) );
		$c = new RemoteEntityId( 'commons', new ItemId( 'Q42' ) );
		$d = new RemoteEntityId( 'wikidata', new ItemId( 'Q43' ) );

		$this->assertTrue( $a->equals( $b ), 'Same repo + same local id should be equal' );
		$this->assertFalse( $a->equals( $c ), 'Different repo should not be equal' );
		$this->assertFalse( $a->equals( $d ), 'Different local id should not be equal' );
	}

	public function testPhpSerializeRoundTrip(): void {
		$original = new RemoteEntityId( 'wikidata', new ItemId( 'Q42' ) );

		$copy = unserialize( serialize( $original ) );

		$this->assertInstanceOf( RemoteEntityId::class, $copy );
		$this->assertSame( 'wikidata:Q42', $copy->getSerialization() );
		$this->assertTrue( $original->equals( $copy ) );
	}
}
