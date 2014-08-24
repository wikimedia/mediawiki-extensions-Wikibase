<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\ReferencedEntitiesFinder;

/**
 * @covers Wikibase\ReferencedEntitiesFinder
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

		$q23Value = new EntityIdValue( new ItemId( 'q23' ) );
		$q24Value = new EntityIdValue( new ItemId( 'q24' ) );

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
			array( new PropertyValueSnak( $p27, $q23Value ) ),
			array( $p27, $q23Value->getEntityId() ),
			"PropertyValueSnak with EntityId"
		);

		$argLists[] = array(
			array(
				new PropertyValueSnak( $p11, $q23Value ),
				new PropertyNoValueSnak( $p27 ),
				new PropertySomeValueSnak( $p44 ),
				new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ),
				new PropertyValueSnak( $p44, $q24Value ),
			),
			array( $p11, $q23Value->getEntityId(), $p27, $p44, $q24Value->getEntityId() ),
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
