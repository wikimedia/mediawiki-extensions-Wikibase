<?php

namespace Wikibase\Lib\Test;
use DataTypes\DataTypeFactory;
use Exception;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\ReferencedUrlFinder;
use Wikibase\Claim;
use Wikibase\Snak;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;
use DataValues\StringValue;
use Wikibase\LibRegistry;

/**
 * Tests for the Wikibase\ReferencedUrlFinder class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
	 * @param Snak[]     $snaks
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

		wfSuppressWarnings(); // suppress warnings about unknown property
		try {
			$actual = $linkFinder->findSnakLinks( $snaks );
			$this->assertEmpty( $actual ); // since $p42 isn't know, this should return nothing

			wfRestoreWarnings();
		} catch ( Exception $exception ) {
			wfRestoreWarnings();
		}
	}
}
