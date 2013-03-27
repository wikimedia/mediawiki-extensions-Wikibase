<?php

namespace Wikibase\Test;
use Wikibase\PropertyEntityLookup;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;
use Wikibase\Statement;
use Wikibase\Claims;
use Wikibase\PropertyValueSnak;
use DataValues\StringValue;

/**
 * Base class for testing the Wikibase\PropertyLookup implementations.
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
abstract class PropertyLookupTest extends \MediaWikiTestCase {

	/**
	 * @var \Wikibase\PropertyLookup
	 */
	protected $propertyLookup;

	/**
	 * @var \Wikibase\Entity[]
	 */
	protected $entities;

	public function getPropertyData() {
		$propertyData = array(
			array(
				'id' => 4,
				'type' => 'wikibase-item',
				'lang' => 'en',
				'label' => 'capital'
			),
			array(
				'id' => 6,
				'type' => 'wikibase-item',
				'lang' => 'en',
				'label' => 'currency'
			),
			array(
				'id' => 7,
				'type' => 'commonsMedia',
				'lang' => 'en',
				'label' => 'flag',
			),
			array(
				'id' => 9,
				'type' => 'string',
				'lang' => 'en',
				'label' => 'country code'
			),
			array(
				'id' => 10,
				'type' => 'wikibase-item',
			)
		);

		$properties = array();

		foreach( $propertyData as $data ) {
			$property = Property::newFromType( $data['type'] );
			$property->setId( new EntityId( Property::ENTITY_TYPE, $data['id'] ) );

			if ( array_key_exists( 'label', $data ) ) {
				$property->setLabel( $data['lang'], $data['label'] );
			}

			$properties[] = $property;
		}

		return $properties;
	}

	public function getItems() {
		$items = array();

		$properties = $this->getPropertyData();

		$snakData = array(
			array( 'property' => clone $properties[0], 'value' => new EntityId( Item::ENTITY_TYPE, 42 ) ),
			array( 'property' => clone $properties[0], 'value' => new EntityId( Item::ENTITY_TYPE, 44 ) ),
			array( 'property' => clone $properties[1], 'value' => new EntityId( Item::ENTITY_TYPE, 45 ) ),
			array( 'property' => clone $properties[2], 'value' => new StringValue( 'Flag of Canada.svg' ) ),
			array( 'property' => clone $properties[4], 'value' => new EntityId( Item::ENTITY_TYPE, 46 ) ),
		);

		$statements = array();

		foreach( $snakData as $data ) {
			$property = $data['property'];
			$statements[] = new Statement(
				new PropertyValueSnak( $property->getId(), $data['value'] )
			);
		}

		$item = Item::newEmpty();
		$itemId = new EntityId( Item::ENTITY_TYPE, 126 );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Canada' );

		$claims = new Claims();

		foreach( $statements as $statement ) {
			$claims->addClaim( $statement );
		}

		$item->setClaims( $claims );

		$items[] = $item;

		// -------------
		$item = $item->copy();

		$itemId = new EntityId( Item::ENTITY_TYPE, 128 );
		$item->setId( $itemId );
		$item->setLabel( 'en', 'Nanada' );

		$statement = new Statement(
			new \Wikibase\PropertyNoValueSnak( $properties[3]->getId() )
		);

		$claims = new Claims();
		$claims->addClaim( $statement );
		$item->setClaims( $claims );

		$items[] = $item;

		return $items;
	}

	public function setUp() {
		parent::setUp();

		$properties = $this->getPropertyData();

		foreach( $properties as $property ) {
			$id = $property->getId()->getPrefixedId();
			$this->entities[$id] = $property;
		}

		$items = $this->getItems();

		foreach ( $items as $item ) {
			$id = $item->getId()->getPrefixedId();
			$this->entities[$id] = $item;
		}
	}

	/**
	 * @param $id
	 *
	 * @return \Wikibase\Entity
	 */
	protected function getEntity( $id ) {
		if ( $id instanceof EntityId ) {
			$id = $id->getPrefixedId();
		}

		return $this->entities[$id];
	}

	public function getClaimsByPropertyLabelProvider() {
		return array(
			array( 'q126', 'capital', 'en', 2 ),
			array( 'q126', 'currency', 'en', 1 ),
			array( 'q126', 'president', 'en', 0 ),
			array( 'q128', 'country code', 'en', 1 )
		);
	}

	/**
	 * @dataProvider getClaimsByPropertyLabelProvider
	 */
	public function testGetClaimsByPropertyLabel( $entityId, $propertyLabel, $langCode, $expected ) {
		$entity = $this->getEntity( $entityId );
		$claims = $this->propertyLookup->getClaimsByPropertyLabel( $entity, $propertyLabel, $langCode );

		$this->assertInstanceOf( '\Wikibase\Claims', $claims );
		$this->assertEquals( $expected, count( $claims ) );
	}

	public function testGetClaimsByPropertyLabel2( ) {
		$entity126 = $this->getEntity( 'q126' );

		$claims = $this->propertyLookup->getClaimsByPropertyLabel( $entity126, 'capital', 'en' );
		$this->assertEquals( 2, count( $claims ) );

		$claims = $this->propertyLookup->getClaimsByPropertyLabel( $entity126, 'country code', 'en' );
		$this->assertEquals( 0, count( $claims ) );

		// try to find a property in another entity, if that property wasn't used by the previous entity.
		$entity128 = $this->getEntity( 'q128' );

		$claims = $this->propertyLookup->getClaimsByPropertyLabel( $entity128, 'country code', 'en' );
		$this->assertEquals( 1, count( $claims ), "property unknown to the first item" );
	}
}
