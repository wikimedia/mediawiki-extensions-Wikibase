<?php

namespace Wikibase\Lib\Test;

use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use Exception;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\LibRegistry;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\ReferencedUrlFinder;
use Wikibase\Snak;

/**
 * @covers Wikibase\ReferencedUrlFinder
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @covers ReferencedUrlFinder
 */
class ReferencedUrlFinderTest extends \MediaWikiTestCase {

	public function snaksProvider() {
		$argLists = array();

		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( new PropertyNoValueSnak( $p42 ) ),
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( new PropertySomeValueSnak( $p42 ) ),
			array() );

		$argLists["PropertyValueSnak with string value"] = array(
			array( new PropertyValueSnak( $p23, new StringValue( 'http://not/a/url' )  ) ),
			array() );

		$argLists["PropertyValueSnak with EntityId"] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' )  ) ),
			array( 'http://acme.com/test' ) );

		return $argLists;
	}

	/**
	 * @dataProvider snaksProvider
	 *
	 * @param Snak[] $snaks
	 * @param EntityId[] $expected
	 */
	public function testFindSnakLinks( array $snaks, array $expected ) {
		$p23 = new EntityId( Property::ENTITY_TYPE, 23 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( $p23, 'string' );
		$dataTypeLookup->setDataTypeForProperty( $p42, 'url' );

		$linkFinder = new ReferencedUrlFinder( $dataTypeLookup );
		$actual = $linkFinder->findSnakLinks( $snaks );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

	public function testFindSnakLinksForUnknownProperty() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$linkFinder = new ReferencedUrlFinder( $dataTypeLookup );

		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$snaks = array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' )  ) );

		$actual = $linkFinder->findSnakLinks( $snaks );
		$this->assertEmpty( $actual ); // since $p42 isn't know, this should return nothing
	}
}
