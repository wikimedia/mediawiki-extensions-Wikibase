<?php

namespace Wikibase\Test;
use DataValue\DataValueObject as DataValueObject;
use Wikibase\PropertyValueSnak as PropertyValueSnak;
use Wikibase\ReferenceObject as ReferenceObject;
use Wikibase\Reference as Reference;
use Wikibase\SnakList as SnakList;
use Wikibase\Snaks as Snaks;

/**
 * Tests for the Wikibase\ReferenceObject class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseReference
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceObjectTest extends \MediaWikiTestCase {

	public function snakListProvider() {
		$snakLists = array();

		$snakLists[] = new SnakList();

		$snakLists[] = new SnakList(
			array( new PropertyValueSnak( 1, new DataValueObject() ) )
		);

		$snakLists[] = new SnakList( array(
			new PropertyValueSnak( 1, new DataValueObject() ),
			new \Wikibase\PropertySomeValueSnak( 2 ),
			new \Wikibase\PropertyNoValueSnak( 3 )
		) );

		return $this->arrayWrap( $snakLists );
	}

	public function instanceProvider() {
		$references = array();

		$references[] = new ReferenceObject();

		$references[] = new ReferenceObject( new SnakList( array( new PropertyValueSnak( 1, new DataValueObject() ) ) ) );

		return $this->arrayWrap( $references );
	}

	/**
	 * @dataProvider snakListProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testConstructor( Snaks $snaks ) {
		$omnomnomReference = new ReferenceObject( $snaks );

		$this->assertInstanceOf( '\Wikibase\Reference', $omnomnomReference );

		$this->assertEquals( $snaks, $omnomnomReference->getSnaks() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetHash( Reference $reference ) {
		$this->assertEquals( $reference->getHash(), $reference->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetSnaks( Reference $reference ) {
		$snaks = $reference->getSnaks();

		$this->assertInstanceOf( '\Wikibase\Snaks', $snaks );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetSnaks( Reference $reference ) {
		$snaks = new SnakList(
			new PropertyValueSnak( 5, new DataValueObject() )
		);

		$reference->setSnaks( $snaks );

		$this->assertEquals( $snaks, $reference->getSnaks() );
	}

}
