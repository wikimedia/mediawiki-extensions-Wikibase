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
class ReferencedEntitiesFinderTest extends \PHPUnit_Framework_TestCase {

	public function snaksProvider() {
		$argLists = array();

		$p11 = new PropertyId( 'p11' );
		$p27 = new PropertyId( 'p27' );
		$p44 = new PropertyId( 'p44' );

		$q23 = new EntityIdValue( new ItemId( 'q23' ) );
		$q24 = new EntityIdValue( new ItemId( 'q24' ) );

		$argLists[] = array(
			array(),
			array(),
			"empty"
		);

		$argLists[] = array(
			array( new PropertyNoValueSnak( $p27 ) ),
			array( $p27 ),
			"Property"
		);

		$argLists[] = array(
			array( new PropertySomeValueSnak( $p27 ) ),
			array( $p27 ),
			"PropertySomeValueSnak"
		);

		$argLists[] = array(
			array( new PropertyValueSnak( $p27, new StringValue( 'onoez' )  ) ),
			array( $p27 ),
			"PropertyValueSnak with string value"
		);

		$argLists[] = array(
			array( new PropertyValueSnak( $p27, $q23 ) ),
			array( $p27, $q23->getEntityId() ),
			"PropertyValueSnak with EntityId"
		);

		$argLists[] = array(
			array(
				new PropertyValueSnak( $p11, $q23 ),
				new PropertyNoValueSnak( $p27 ),
				new PropertySomeValueSnak( $p44 ),
				new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ),
				new PropertyValueSnak( $p44, $q24 ),
			),
			array( $p11, $q23->getEntityId(), $p27, $p44, $q24->getEntityId() ),
			"PropertyValueSnak with EntityId"
		);

		return $argLists;
	}

	/**
	 * @dataProvider snaksProvider
	 *
	 * @param Snak[] $snaks
	 * @param EntityId[] $expected
	 * @param string $message
	 */
	public function testFindSnakLinks( array $snaks, array $expected, $message ) {
		$linkFinder = new ReferencedEntitiesFinder();

		$actual = $linkFinder->findSnakLinks( $snaks );

		$expected = array_values( $expected );
		$actual = array_values( $actual );

		asort( $expected );
		asort( $actual );

		$this->assertEquals( $expected, $actual, $message );
	}

}
