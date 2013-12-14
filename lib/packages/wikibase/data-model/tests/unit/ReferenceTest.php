<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * @covers Wikibase\DataModel\Reference
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseReference
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceTest extends \PHPUnit_Framework_TestCase {

	public function snakListProvider() {
		$snakLists = array();

		$snakLists[] = new SnakList();

		$snakLists[] = new SnakList(
			array( new PropertyValueSnak( new EntityId( Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ) )
		);

		$snakLists[] = new SnakList( array(
			new PropertyValueSnak( new EntityId( Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ),
			new PropertySomeValueSnak( new EntityId( Property::ENTITY_TYPE, 2 ) ),
			new PropertyNoValueSnak( new EntityId( Property::ENTITY_TYPE, 3 ) )
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

		$references[] = new Reference( new SnakList( array( new PropertyValueSnak(
			new EntityId( Property::ENTITY_TYPE, 1 ),
			new StringValue( 'a' )
		) ) ) );

		$argLists = array();

		foreach ( $references as $reference ) {
			$argLists[] = array( $reference );
		}

		return $argLists;
	}

	/**
	 * @dataProvider snakListProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testConstructor( Snaks $snaks ) {
		$omnomnomReference = new Reference( $snaks );

		$this->assertInstanceOf( '\Wikibase\Reference', $omnomnomReference );

		$this->assertEquals( $snaks, $omnomnomReference->getSnaks() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetHash( Reference $reference ) {
		$this->assertEquals( $reference->getHash(), $reference->getHash() );
		$this->assertInternalType( 'string', $reference->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSnaks( Reference $reference ) {
		$snaks = $reference->getSnaks();

		$this->assertInstanceOf( '\Wikibase\Snaks', $snaks );
	}

	/**
	 * Provides the same reference with its snak list in an unordered and in the ordered state as it
	 * would result from issuing SnakList::orderByProperty().
	 * @return array
	 */
	public function unorderedReferenceProvider() {
		$ids = array(
			new EntityId( Property::ENTITY_TYPE, 1 ),
			new EntityId( Property::ENTITY_TYPE, 2 ),
			new EntityId( Property::ENTITY_TYPE, 3 ),
			new EntityId( Property::ENTITY_TYPE, 4 ),
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

		foreach( $snakListArgs as $snakLists ) {
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

}
