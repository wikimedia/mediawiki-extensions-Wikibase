<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\SnakList;
use Wikibase\Snaks;

/**
 * @covers Wikibase\Reference
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
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
			array( new PropertyValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ) )
		);

		$snakLists[] = new SnakList( array(
			new PropertyValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ),
			new \Wikibase\PropertySomeValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 2 ) ),
			new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 3 ) )
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
			new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ),
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

}
