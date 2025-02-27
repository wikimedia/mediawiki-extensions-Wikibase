<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel;

use DataValues\Serializers\DataValueSerializer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\TypedSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers \Wikibase\DataModel\Serializers\SerializerFactory
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SerializerFactoryTest extends TestCase {
	use \MediaWikiCoversValidator;

	private function buildSerializerFactory(): SerializerFactory {
		return new SerializerFactory( new DataValueSerializer() );
	}

	private function assertSerializesWithoutException( Serializer $serializer, object $object ): void {
		$serializer->serialize( $object );
		$this->assertTrue( true, 'No exception occurred during serialization' );
	}

	public function testNewEntitySerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newEntitySerializer(),
			new Item()
		);

		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newEntitySerializer(),
			Property::newFromType( 'string' )
		);
	}

	public function testNewItemSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newItemSerializer(),
			new Item()
		);
	}

	public function testNewPropertySerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newPropertySerializer(),
			Property::newFromType( 'string' )
		);
	}

	public function testNewSiteLinkSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSiteLinkSerializer(),
			new SiteLink( 'enwiki', 'Nyan Cat' )
		);
	}

	public function testNewStatementSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newStatementSerializer(),
			new Statement( new PropertyNoValueSnak( 42 ) )
		);
	}

	public function testNewStatementListSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newStatementListSerializer(),
			new StatementList()
		);
	}

	public function testNewReferencesSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newReferencesSerializer(),
			new ReferenceList()
		);
	}

	public function testNewReferenceSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newReferenceSerializer(),
			new Reference()
		);
	}

	public function testNewSnakListSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSnakListSerializer(),
			new SnakList( [] )
		);
	}

	public function testNewSnakSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSnakSerializer(),
			new PropertyNoValueSnak( 42 )
		);
	}

	public function testFactoryCreateWithUnexpectedValue(): void {
		$this->expectException( InvalidArgumentException::class );
		new SerializerFactory( new DataValueSerializer(), 1.0 );
	}

	public function testNewTypedSnakSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTypedSnakSerializer(),
			new TypedSnak( new PropertyNoValueSnak( 42 ), 'kittens' )
		);
	}

	public function testNewTermSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTermSerializer(),
			new Term( 'en', 'Foo' )
		);
	}

	public function testNewTermListSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTermListSerializer(),
			new TermList( [ new Term( 'de', 'Foo' ) ] )
		);
	}

	public function testNewAliasGroupSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newAliasGroupSerializer(),
			new AliasGroup( 'en', [ 'foo', 'bar' ] )
		);
	}

	public function testNewAliasGroupListSerializer(): void {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newAliasGroupListSerializer(),
			new AliasGroupList( [ new AliasGroup( 'de', [ 'AA', 'BB' ] ) ] )
		);
	}

	public function testSerializeSnaksWithoutHashConstant(): void {
		$this->assertSame(
			// expected:
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH |
			SerializerFactory::OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH |
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH,
			// actual:
			SerializerFactory::OPTION_SERIALIZE_SNAKS_WITHOUT_HASH
		);
	}

}
