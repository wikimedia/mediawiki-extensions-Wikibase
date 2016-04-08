<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Serializers\DataValueSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
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
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SerializerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function buildSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer() );
	}

	private function assertSerializesWithoutException( Serializer $serializer, $object ) {
		$serializer->serialize( $object );
		$this->assertTrue( true, 'No exception occurred during serialization' );
	}

	public function testNewEntitySerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newEntitySerializer(),
			new Item()
		);

		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newEntitySerializer(),
			Property::newFromType( 'string' )
		);
	}

	public function testNewItemSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newItemSerializer(),
			new Item()
		);
	}

	public function testNewPropertySerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newPropertySerializer(),
			Property::newFromType( 'string' )
		);
	}

	public function testNewSiteLinkSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSiteLinkSerializer(),
			new SiteLink( 'enwiki', 'Nyan Cat' )
		);
	}

	public function testNewStatementSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newStatementSerializer(),
			new Statement( new PropertyNoValueSnak( 42 ) )
		);
	}

	public function testNewStatementListSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newStatementListSerializer(),
			new StatementList()
		);
	}

	public function testNewReferencesSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newReferencesSerializer(),
			new ReferenceList()
		);
	}

	public function testNewReferenceSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newReferenceSerializer(),
			new Reference()
		);
	}

	public function testNewSnakListSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSnakListSerializer(),
			new SnakList( array() )
		);
	}

	public function testNewSnakSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newSnakSerializer(),
			new PropertyNoValueSnak( 42 )
		);
	}

	public function testFactoryCreateWithUnexpectedValue() {
		$this->setExpectedException( 'InvalidArgumentException' );
		new SerializerFactory( new DataValueSerializer(), 1.0 );
	}

	public function testNewSnakListSerializerWithUseObjectsForMaps() {
		$factory = new SerializerFactory( new DataValueSerializer(), SerializerFactory::OPTION_OBJECTS_FOR_MAPS );
		$serializer = $factory->newSnakListSerializer();
		$this->assertAttributeSame( true, 'useObjectsForMaps' , $serializer );
	}

	public function testNewTypedSnakSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTypedSnakSerializer(),
			new TypedSnak( new PropertyNoValueSnak( 42 ), 'kittens' )
		);
	}

	public function testNewTermSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTermSerializer(),
			new Term( 'en', 'Foo' )
		);
	}

	public function testNewTermListSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newTermListSerializer(),
			new TermList( array( new Term( 'de', 'Foo' ) ) )
		);
	}

	public function testNewAliasGroupSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newAliasGroupSerializer(),
			new AliasGroup( 'en', array( 'foo', 'bar' ) )
		);
	}

	public function testNewAliasGroupListSerializer() {
		$this->assertSerializesWithoutException(
			$this->buildSerializerFactory()->newAliasGroupListSerializer(),
			new AliasGroupList( array( new AliasGroup( 'de', array( 'AA', 'BB' ) ) ) )
		);
	}

}
