<?php

namespace Wikibase\Test;
use Wikibase\PropertyEntityLookup;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;

/**
 * Tests for the Wikibase\PropertyEntityLookup class.
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
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyEntityLookupTest extends PropertyLookupTest {

	/**
	 * @var \Wikibase\EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var \Wikibase\PropertyEntityLookup
	 */
	protected $propertyLookup;

	public function setUp() {
		parent::setUp();

		$this->entityLookup = new \Wikibase\Test\MockRepository();

		foreach ( $this->entities as $entity ) {
			$this->entityLookup->putEntity( $entity );
		}

		$this->propertyLookup = new PropertyEntityLookup( $this->entityLookup );
	}

	public function testConstructor() {
		$instance = new PropertyEntityLookup( $this->entityLookup );
		$this->assertInstanceOf( '\Wikibase\PropertyEntityLookup', $instance );
	}

	/**
	 * @dataProvider getClaimsByPropertyLabelProvider
	 */
	public function testGetClaimsByPropertyLabel( $entityId, $propertyLabel, $langCode, $expected ) {
		if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
			$this->markTestSkipped( "getClaimsByPropertyLabel is experimental" );
		}

		parent::testGetClaimsByPropertyLabel( $entityId, $propertyLabel, $langCode, $expected );
	}

	public function testGetClaimsByPropertyLabel2( ) {
		if ( !defined( 'WB_EXPERIMENTAL_FEATURES' ) || !WB_EXPERIMENTAL_FEATURES ) {
			$this->markTestSkipped( "getClaimsByPropertyLabel is experimental" );
		}

		parent::testGetClaimsByPropertyLabel2();
	}

	public function getPropertyLabelProvider() {
		return array(
			array( 'capital', 'en', new EntityId( Property::ENTITY_TYPE, 4 ) ),
			array( 'flag', 'en', new EntityId( Property::ENTITY_TYPE, 7 ) ),
			array( false, 'en', new EntityId( Property::ENTITY_TYPE, 10 ) )
		);
	}

	/**
	 * @depends testGetClaimsByPropertyLabel
	 * @dataProvider getPropertyLabelProvider
	 */
	public function testGetPropertyLabel( $expected, $lang, $id ) {
		$label = $this->propertyLookup->getPropertyLabel( $id, $lang );
		$this->assertEquals( $expected, $label );
	}
}
