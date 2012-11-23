<?php

namespace Wikibase\Test;
use DataValues\StringValue;
use Wikibase\PropertyValueSnak;
use Wikibase\ReferenceObject;
use Wikibase\Reference;
use Wikibase\SnakList;
use Wikibase\Snaks;

/**
 * Tests for the Wikibase\ReferenceObject class.
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
			array( new PropertyValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ) )
		);

		$snakLists[] = new SnakList( array(
			new PropertyValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ), new StringValue( 'a' ) ),
			new \Wikibase\PropertySomeValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 2 ) ),
			new \Wikibase\PropertyNoValueSnak( new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 3 ) )
		) );

		return $this->arrayWrap( $snakLists );
	}

	public function instanceProvider() {
		$references = array();

		$references[] = new ReferenceObject();

		$references[] = new ReferenceObject( new SnakList( array( new PropertyValueSnak(
			new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ),
			new StringValue( 'a' )
		) ) ) );

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
