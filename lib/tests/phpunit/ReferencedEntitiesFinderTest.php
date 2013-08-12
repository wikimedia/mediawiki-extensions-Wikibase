<?php

namespace Wikibase\Lib\Test;
use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use Wikibase\Claims;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\Claim;
use Wikibase\Statement;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;
use Wikibase\SnakList;
use Wikibase\Snak;
use DataValues\StringValue;
use Wikibase\LibRegistry;
use Wikibase\Settings;
use Wikibase\ReferenceList;
use Wikibase\Reference;

/**
 * Tests for the Wikibase\ReferencedEntitiesFinder class.
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
 * @group EntityLinkFinder
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ReferencedEntitiesFinderTest extends \MediaWikiTestCase {

	public function claimsProvider() {
		$argLists = array();

		$p11 = new EntityId( Property::ENTITY_TYPE, 11 );
		$p42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$p44 = new EntityId( Property::ENTITY_TYPE, 44 );

		$q23 = new EntityId( Item::ENTITY_TYPE, 23 );
		$q24 = new EntityId( Item::ENTITY_TYPE, 24 );

		$argLists[] = array(
			array(),
			array(),
			"empty" );

		$argLists[] = array(
			array( new PropertyNoValueSnak( $p42 ) ),
			array( $p42 ),
			"Property" );

		$argLists[] = array(
			array( new PropertySomeValueSnak( $p42 ) ),
			array( $p42 ),
			"PropertySomeValueSnak" );

		$argLists[] = array(
			array( new PropertyValueSnak( $p42, new StringValue( 'onoez' )  ) ),
			array( $p42 ),
			"PropertyValueSnak with string value" );

		$argLists[] = array(
			array( new PropertyValueSnak( $p42, $q23 ) ),
			array( $p42, $q23 ),
			"PropertyValueSnak with EntityId" );

		$argLists[] = array(
			array(
				new PropertyValueSnak( $p11, $q23 ),
				new PropertyNoValueSnak( $p42 ),
				new PropertySomeValueSnak( $p44 ),
				new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ),
				new PropertyValueSnak( $p44, $q24 ),
			),
			array( $p11, $q23, $p42, $p44, $q24 ),
			"PropertyValueSnak with EntityId" );

		return $argLists;
	}

	/**
	 * @dataProvider claimsProvider
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
