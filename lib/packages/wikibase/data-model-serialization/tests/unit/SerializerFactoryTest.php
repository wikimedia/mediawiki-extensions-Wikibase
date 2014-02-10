<?php

namespace Tests\Wikibase\DataModel;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SerializerFactoryTest extends \PHPUnit_Framework_TestCase {

	private function buildSerializerFactory() {
		return new SerializerFactory( new DataValueSerializer() );
	}

	public function testNewClaimSerializer() {
		$this->assertTrue( $this->buildSerializerFactory()->newClaimSerializer()->isSerializerFor(
			new Claim( new PropertyNoValueSnak( 42 ) )
		) );
	}

	public function testNewReferencesSerializer() {
		$this->assertTrue( $this->buildSerializerFactory()->newReferencesSerializer()->isSerializerFor(
			new ReferenceList()
		) );
	}

	public function testNewReferenceSerializer() {
		$this->assertTrue( $this->buildSerializerFactory()->newReferenceSerializer()->isSerializerFor(
			new Reference()
		) );
	}

	public function testNewSnaksSerializer() {
		$this->assertTrue( $this->buildSerializerFactory()->newSnaksSerializer()->isSerializerFor(
			new SnakList( array() )
		) );
	}

	public function testNewSnakSerializer() {
		$this->assertTrue( $this->buildSerializerFactory()->newSnakSerializer()->isSerializerFor(
			new PropertyNoValueSnak( 42 )
		) );
	}
}