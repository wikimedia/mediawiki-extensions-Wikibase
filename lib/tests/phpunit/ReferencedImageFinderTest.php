<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\ReferencedImageFinder;
use Wikibase\Snak;

/**
 * @covers Wikibase\ReferencedImageFinderTest
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ReferencedImageFinderTest extends \MediaWikiTestCase {

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

		$argLists["PropertyValueSnak with string value"] = array(
			array( new PropertyValueSnak( $p23, new StringValue( 'not a file' )  ) ),
			array() );

		$argLists["PropertyValueSnak with image"] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'File:Image.jpg' ) ) ),
			array( 'File:Image.jpg' ) );

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
		$dataTypeLookup->setDataTypeForProperty( $p42, 'commonsMedia' );

		$imageFinder = new ReferencedImageFinder( $dataTypeLookup );
		$actual = $imageFinder->findFromSnaks( $snaks );

		$this->assertArrayEquals( $expected, $actual ); // assertArrayEquals doesn't take a message :(
	}

	public function testFindFromSnaksForUnknownProperty() {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$imageFinder = new ReferencedImageFinder( $dataTypeLookup );

		$p42 = new PropertyId( 'p42' );
		$snaks = array( new PropertyValueSnak( $p42, new StringValue( '!nyan' )  ) );

		$actual = $imageFinder->findFromSnaks( $snaks );
		$this->assertEmpty( $actual ); // since $p42 isn't know, this should return nothing
	}

}
