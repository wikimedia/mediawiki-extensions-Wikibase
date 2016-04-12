<?php

namespace Wikibase\DataModel\Tests;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Reference
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseReference
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceTest extends \PHPUnit_Framework_TestCase {

	public function snakListProvider() {
		$snakLists = array();

		$snakLists[] = new SnakList();

		$snakLists[] = new SnakList(
			array( new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ) )
		);

		$snakLists[] = new SnakList( array(
			new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P2' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P3' ) )
		) );

		$argLists = array();

		foreach ( $snakLists as $snakList ) {
			$argLists[] = array( $snakList );
		}

		return $argLists;
	}

	public function instanceProvider() {
		$references = array();

		$references[] = new Reference();

		$references[] = new Reference( array(
			new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
		) );

		$references[] = new Reference( array(
			new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'a' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P2' ) ),
		) );

		$argLists = array();

		foreach ( $references as $reference ) {
			$argLists[] = array( $reference );
		}

		return $argLists;
	}

	/**
	 * @dataProvider snakListProvider
	 */
	public function testConstructor( SnakList $snaks ) {
		$omnomnomReference = new Reference( $snaks );

		$this->assertInstanceOf( 'Wikibase\DataModel\Reference', $omnomnomReference );

		$this->assertEquals( $snaks, $omnomnomReference->getSnaks() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetHashReturnsString( Reference $reference ) {
		$this->assertInternalType( 'string', $reference->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetHashIsStable( Reference $reference ) {
		$this->assertEquals( $reference->getHash(), $reference->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetHashIsTheSameForInstanceWithSameValue( Reference $reference ) {
		$newRef = unserialize( serialize( $reference ) );
		$this->assertEquals( $newRef->getHash(), $reference->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSnaks( Reference $reference ) {
		$snaks = $reference->getSnaks();

		$this->assertInstanceOf( 'Wikibase\DataModel\Snak\SnakList', $snaks );
	}

	/**
	 * Provides the same reference with its snak list in an unordered and in the ordered state as it
	 * would result from issuing SnakList::orderByProperty().
	 * @return array
	 */
	public function unorderedReferenceProvider() {
		$ids = array(
			new PropertyId( 'P1' ),
			new PropertyId( 'P2' ),
			new PropertyId( 'P3' ),
			new PropertyId( 'P4' ),
		);

		$snakListArgs = array(
			array(
				new SnakList( array(
					new PropertyValueSnak( $ids[0], new StringValue( 'a' ) ),
					new PropertyNoValueSnak( $ids[1] ),
					new PropertySomeValueSnak( $ids[0] ),
				) ),
				new SnakList( array(
					new PropertyValueSnak( $ids[0], new StringValue( 'a' ) ),
					new PropertySomeValueSnak( $ids[0] ),
					new PropertyNoValueSnak( $ids[1] ),
				) )
			),
			array(
				new SnakList( array(
					new PropertyNoValueSnak( $ids[1] ),
					new PropertyNoValueSnak( $ids[0] ),
					new PropertySomeValueSnak( $ids[1] ),
					new PropertyNoValueSnak( $ids[2] ),
					new PropertySomeValueSnak( $ids[0] ),
					new PropertyNoValueSnak( $ids[3] ),
				) ),
				new SnakList( array(
					new PropertyNoValueSnak( $ids[1] ),
					new PropertySomeValueSnak( $ids[1] ),
					new PropertyNoValueSnak( $ids[0] ),
					new PropertySomeValueSnak( $ids[0] ),
					new PropertyNoValueSnak( $ids[2] ),
					new PropertyNoValueSnak( $ids[3] ),
				) ),
			),
		);

		$args = array();

		foreach ( $snakListArgs as $snakLists ) {
			$args[] = array(
				new Reference( $snakLists[0] ),
				new Reference( $snakLists[1] ),
			);
		}

		return $args;
	}

	/**
	 * @dataProvider unorderedReferenceProvider
	 */
	public function testUnorderedReference( Reference $unorderedReference, Reference $orderedReference ) {
		$this->assertEquals( $unorderedReference->getHash(), $orderedReference->getHash() );
	}

	public function testReferenceEqualsItself() {
		$reference = new Reference( array( new PropertyNoValueSnak( 42 ) ) );
		$this->assertTrue( $reference->equals( $reference ) );
	}

	public function testReferenceDoesNotEqualReferenceWithDifferentSnakProperty() {
		$reference0 = new Reference( array( new PropertyNoValueSnak( 42 ) ) );
		$reference1 = new Reference( array( new PropertyNoValueSnak( 1337 ) ) );
		$this->assertFalse( $reference0->equals( $reference1 ) );
	}

	public function testReferenceDoesNotEqualReferenceWithMoreSnaks() {
		$reference0 = new Reference( array( new PropertyNoValueSnak( 42 ) ) );

		$reference1 = new Reference( array(
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 42 ),
		) );

		$this->assertFalse( $reference0->equals( $reference1 ) );
	}

	public function testReferenceEqualsReferenceWithDifferentSnakOrder() {
		$reference0 = new Reference( array(
			new PropertyNoValueSnak( 1337 ),
			new PropertyNoValueSnak( 42 ),
		) );

		$reference1 = new Reference( array(
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 1337 ),
		) );

		$this->assertTrue( $reference0->equals( $reference1 ) );
	}

	public function testReferencesWithDifferentSnakOrderHaveTheSameHash() {
		$reference0 = new Reference( array(
			new PropertySomeValueSnak( 1337 ),
			new PropertyNoValueSnak( 1337 ),
			new PropertyNoValueSnak( 42 ),
		) );

		$reference1 = new Reference( array(
			new PropertyNoValueSnak( 1337 ),
			new PropertyNoValueSnak( 42 ),
			new PropertySomeValueSnak( 1337 ),
		) );

		$this->assertSame( $reference0->getHash(), $reference1->getHash() );
	}

	public function testCanConstructWithSnakArray() {
		$snaks = array(
			new PropertyNoValueSnak( 1 ),
			new PropertyNoValueSnak( 3 ),
			new PropertyNoValueSnak( 2 ),
		);

		$reference = new Reference( $snaks );

		$this->assertEquals(
			new SnakList( $snaks ),
			$reference->getSnaks()
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidConstructorArguments_constructorThrowsException( $snaks ) {
		new Reference( $snaks );
	}

	public function invalidConstructorArgumentsProvider() {
		$id1 = new PropertyId( 'P1' );

		return array(
			array( null ),
			array( false ),
			array( 1 ),
			array( 0.1 ),
			array( 'string' ),
			array( $id1 ),
			array( new PropertyNoValueSnak( $id1 ) ),
			array( new PropertyValueSnak( $id1, new StringValue( 'a' ) ) ),
			array( array( new SnakList() ) ),
			array( new Reference() ),
		);
	}

	public function testIsEmpty() {
		$emptyReference = new Reference();
		$this->assertTrue( $emptyReference->isEmpty() );

		$referenceWithSnak = new Reference( array(
			new PropertyNoValueSnak( 1 ),
		) );
		$this->assertFalse( $referenceWithSnak->isEmpty() );

		$referenceWithSnaks = new Reference( array(
			new PropertyNoValueSnak( 1 ),
			new PropertyNoValueSnak( 2 ),
		) );
		$this->assertFalse( $referenceWithSnaks->isEmpty() );
	}

}
