<?php

namespace Wikibase\DataModel\Tests\Entity;

use ReflectionClass;
use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\DataModel\Entity\EntityId
 * @uses Wikibase\DataModel\Entity\ItemId
 * @uses Wikibase\DataModel\Entity\PropertyId
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group EntityIdTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityIdTest extends \PHPUnit_Framework_TestCase {

	public function instanceProvider() {
		$ids = array();

		$ids[] = array( new ItemId( 'Q1' ), '' );
		$ids[] = array( new ItemId( 'Q42' ), '' );
		$ids[] = array( new ItemId( 'Q31337' ), '' );
		$ids[] = array( new ItemId( 'Q2147483647' ), '' );
		$ids[] = array( new ItemId( ':Q2147483647' ), '' );
		$ids[] = array( new ItemId( 'foo:Q2147483647' ), 'foo' );
		$ids[] = array( new PropertyId( 'P101010' ), '' );
		$ids[] = array( new PropertyId( 'foo:bar:P101010' ), 'foo' );

		return $ids;
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testEqualsSimple( EntityId $id ) {
		$this->assertTrue( $id->equals( $id ) );
		$this->assertTrue( $id->equals( unserialize( serialize( $id ) ) ) );
		$this->assertFalse( $id->equals( $id->getSerialization() ) );
		$this->assertFalse( $id->equals( $id->getEntityType() ) );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerializationRoundtrip( EntityId $id ) {
		$this->assertEquals( $id, unserialize( serialize( $id ) ) );
	}

	public function testDeserializationCompatibility() {
		$v05serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":15:{["item","Q123"]}';

		$this->assertEquals(
			new ItemId( 'q123' ),
			unserialize( $v05serialization )
		);
	}

	/**
	 * This test will change when the serialization format changes.
	 * If it is being changed intentionally, the test should be updated.
	 * It is just here to catch unintentional changes.
	 */
	public function testSerializationStability() {
		$v05serialization = 'C:32:"Wikibase\DataModel\Entity\ItemId":15:{["item","Q123"]}';
		$id = new ItemId( 'q123' );

		$this->assertEquals(
			serialize( $id ),
			$v05serialization
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testReturnTypeOfToString( EntityId $id ) {
		$this->assertInternalType( 'string', $id->__toString() );
	}

	public function testIsForeign() {
		$this->assertFalse( ( new ItemId( 'Q42' ) )->isForeign() );
		$this->assertFalse( ( new ItemId( ':Q42' ) )->isForeign() );
		$this->assertTrue( ( new ItemId( 'foo:Q42' ) )->isForeign() );
		$this->assertFalse( ( new PropertyId( ':P42' ) )->isForeign() );
		$this->assertTrue( ( new PropertyId( 'foo:P42' ) )->isForeign() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetRepositoryName( EntityId $id, $repoName ) {
		$this->assertSame( $repoName, $id->getRepositoryName() );
	}

	public function serializationSplitProvider() {
		return array(
			array( 'Q42', array( '', '', 'Q42' ) ),
			array( 'foo:Q42', array( 'foo', '', 'Q42' ) ),
			array( '0:Q42', array( '0', '', 'Q42' ) ),
			array( 'foo:bar:baz:Q42', array( 'foo', 'bar:baz', 'Q42' ) ),
		);
	}

	/**
	 * @dataProvider serializationSplitProvider
	 */
	public function testSplitSerialization( $serialization, $split ) {
		$this->assertSame( $split, EntityId::splitSerialization( $serialization ) );
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testSplitSerializationFails_GivenInvalidSerialization( $serialization ) {
		$this->setExpectedException( InvalidArgumentException::class );
		EntityId::splitSerialization( $serialization );
	}

	/**
	 * @dataProvider serializationSplitProvider
	 */
	public function testJoinSerialization( $serialization, $split ) {
		$this->assertSame( $serialization, EntityId::joinSerialization( $split ) );
	}

	/**
	 * @dataProvider invalidJoinSerializationDataProvider
	 */
	public function testJoinSerializationFails_GivenEmptyId( $parts ) {
		$this->setExpectedException( InvalidArgumentException::class );
		EntityId::joinSerialization( $parts );
	}

	public function invalidJoinSerializationDataProvider() {
		return array(
			array( array( 'Q42', '', '' ) ),
			array( array( '', 'Q42', '' ) ),
			array( array( 'foo', 'Q42', '' ) ),
		);
	}

	public function testGivenNotNormalizedSerialization_splitSerializationReturnsNormalizedParts() {
		$this->assertSame( array( '', '', 'Q42' ), EntityId::splitSerialization( ':Q42' ) );
		$this->assertSame( array( 'foo', 'bar', 'Q42' ), EntityId::splitSerialization( ':foo:bar:Q42' ) );
	}

	public function localPartDataProvider() {
		return array(
			array( 'Q42', 'Q42' ),
			array( ':Q42', 'Q42' ),
			array( 'foo:Q42', 'Q42' ),
			array( 'foo:bar:Q42', 'bar:Q42' ),
		);
	}

	/**
	 * @dataProvider localPartDataProvider
	 */
	public function testGetLocalPart( $serialization, $localPart ) {
		$id = new ItemId( $serialization );
		$this->assertSame( $localPart, $id->getLocalPart() );
	}

	public function invalidSerializationProvider() {
		return array(
			array( 's p a c e s:Q42' ),
			array( '::Q42' ),
			array( '' ),
			array( ':' ),
			array( 42 ),
			array( null ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testConstructor( $serialization ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$mock = $this->getMockBuilder( EntityId::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$constructor = ( new ReflectionClass( EntityId::class ) )->getConstructor();
		$constructor->invoke( $mock, $serialization );
	}

}
