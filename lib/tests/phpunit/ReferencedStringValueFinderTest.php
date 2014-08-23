<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\ReferencedStringValueFinder;

/**
 * @covers Wikibase\ReferencedUrlFinder
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedStringValueFinderTest extends \MediaWikiTestCase {

	public function snaksProvider() {
		$argLists = array();

		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( new PropertyNoValueSnak( $p42 ) ),
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( new PropertySomeValueSnak( $p42 ) ),
			array() );

		$argLists["PropertyValueSnak with string value and wrong data type"] = array(
			array( new PropertyValueSnak( $p23, new StringValue( 'not an url' ) ) ),
			array() );

		$argLists["PropertyValueSnak with string value and correct data type"] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ),
			array( 'http://acme.com/test' ) );

		return $argLists;
	}

	/**
	 * @dataProvider snaksProvider
	 *
	 * @param Snak[] $snaks
	 * @param string[] $expected
	 */
	public function testFindFromSnaks( array $snaks, array $expected ) {
		$p23 = new PropertyId( 'p23' );
		$p42 = new PropertyId( 'p42' );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( $p23, 'string' );
		$dataTypeLookup->setDataTypeForProperty( $p42, 'url' );

		$linkFinder = new ReferencedStringValueFinder( $dataTypeLookup, 'url' );
		$actual = $linkFinder->findFromSnaks( $snaks );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

	public function testFindFromSnaksForUnknownProperty() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$linkFinder = new ReferencedStringValueFinder( $dataTypeLookup, 'url' );

		$p42 = new PropertyId( 'p42' );
		$snaks = array( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' )  ) );

		$actual = $linkFinder->findFromSnaks( $snaks );
		$this->assertEmpty( $actual ); // since $p42 isn't know, this should return nothing
	}

}
