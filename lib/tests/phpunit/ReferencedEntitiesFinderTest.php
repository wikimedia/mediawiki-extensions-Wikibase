<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\LibRegistry;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\Snak;

/**
 * @covers Wikibase\ReferencedEntitiesFinder
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group EntityLinkFinder
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ReferencedEntitiesFinderTest extends \MediaWikiTestCase {

	public function snaksProvider() {
		$argLists = array();

		$p11 = new EntityIdValue( new PropertyId( 'p11' ) );
		$p27 = new EntityIdValue( new PropertyId( 'p27' ) );
		$p44 = new EntityIdValue( new PropertyId( 'p44' ) );

		$q23 = new EntityIdValue( new ItemId( 'q23' ) );
		$q24 = new EntityIdValue( new ItemId( 'q24' ) );

		$argLists[] = array(
			array(),
			array(),
			"empty" );

		$argLists[] = array(
			array( new PropertyNoValueSnak( $p27 ) ),
			array( $p27 ),
			"Property" );

		$argLists[] = array(
			array( new PropertySomeValueSnak( $p27 ) ),
			array( $p27 ),
			"PropertySomeValueSnak" );

		$argLists[] = array(
			array( new PropertyValueSnak( $p27, new StringValue( 'onoez' )  ) ),
			array( $p27 ),
			"PropertyValueSnak with string value" );

		$argLists[] = array(
			array( new PropertyValueSnak( $p27, $q23 ) ),
			array( $p27, $q23 ),
			"PropertyValueSnak with EntityId" );

		$argLists[] = array(
			array(
				new PropertyValueSnak( $p11, $q23 ),
				new PropertyNoValueSnak( $p27 ),
				new PropertySomeValueSnak( $p44 ),
				new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ),
				new PropertyValueSnak( $p44, $q24 ),
			),
			array( $p11, $q23, $p27, $p44, $q24 ),
			"PropertyValueSnak with EntityId" );

		return $argLists;
	}

	/**
	 * @dataProvider snaksProvider
	 *
	 * @param Snak[]     $snaks
	 * @param EntityId[] $expected
	 * @param            $message
	 */
	public function testFindSnakLinks( array $snaks, array $expected, $message ) {
		$linkFinder = new ReferencedEntitiesFinder();

		$actual = $linkFinder->findSnakLinks( $snaks );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

}
