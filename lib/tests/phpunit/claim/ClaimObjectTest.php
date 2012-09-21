<?php

namespace Wikibase\Test;
use Wikibase\ClaimObject as ClaimObject;
use Wikibase\Claim as Claim;
use Wikibase\Snak as Snak;
use \DataValue\StringValue as StringValue;
use \Wikibase\SnakList as SnakList;
use \Wikibase\Snaks as Snaks;

/**
 * Tests for the Wikibase\ClaimObject class.
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
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimObjectTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( new \Wikibase\InstanceOfSnak( 42 ) );

		$argLists[] = array( new \Wikibase\InstanceOfSnak( 42 ), new SnakList() );

		$argLists[] = array(
			new \Wikibase\InstanceOfSnak( 42 ),
			new \Wikibase\SnakList( array(
				new \Wikibase\PropertyValueSnak( 1, new StringValue( 'a' ) ),
				new \Wikibase\PropertySomeValueSnak( 2 ),
				new \Wikibase\PropertyNoValueSnak( 3 )
			) )
		);

		return $argLists;
	}

	public function instanceProvider() {
		return array_map(
			function( Snak $snak, Snaks $qualifiers = null ) {
				return array( new ClaimObject( $snak, $qualifiers ) );
			},
			$this->constructorProvider()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param \Wikibase\Snak $snak
	 * @param null|\Wikibase\Snaks $qualifiers
	 */
	public function testConstructor( Snak $snak, Snaks $qualifiers = null ) {
		$claim = new ClaimObject( $snak, $qualifiers );

		$this->assertInstanceOf( '\Wikibase\Claim', $claim );

		$this->assertEquals( $snak, $claim->getMainSnak() );

		if ( $qualifiers === null ) {
			$this->assertEquals( 0, count( $claim->getQualifiers() ) );
		}
		else {
			$this->assertEquals(
				$qualifiers,
				$claim->getQualifiers()
			);
		}
	}

	public function testSetMainSnak() {
		$claim = new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$snak = new \Wikibase\PropertyNoValueSnak( 41 );
		$claim->setMainSnak( $snak );
		$this->assertEquals( $snak, $claim->getMainSnak() );

		$snak = new \Wikibase\PropertyValueSnak( 43, new StringValue( 'a' ) );
		$claim->setMainSnak( $snak );
		$this->assertEquals( $snak, $claim->getMainSnak() );

		$snak = new \Wikibase\InstanceOfSnak( 42 );
		$claim->setMainSnak( $snak );
		$this->assertEquals( $snak, $claim->getMainSnak() );
	}

	public function testSetQualifiers() {
		$claim = new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$qualifiers = new SnakList();
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );

		$qualifiers = new SnakList( array( new \Wikibase\PropertyValueSnak( 42, new StringValue( 'a' ) ) ) );
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );

		$qualifiers = new SnakList( array(
			new \Wikibase\PropertyValueSnak( 42, new StringValue( 'a' ) ),
			new \Wikibase\PropertySomeValueSnak( 2 ),
			new \Wikibase\PropertyNoValueSnak( 3 )
		) );
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );
	}

}
