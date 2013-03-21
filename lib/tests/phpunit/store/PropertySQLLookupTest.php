<?php

namespace Wikibase\Test;
use Wikibase\PropertySQLLookup;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Claims;
use Wikibase\PropertyValueSnak;
use DataValues\StringValue;

/**
 * Tests for the Wikibase\PropertySQLLookup class.
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
 */
class PropertySQLLookupTest extends \MediaWikiTestCase {

	protected $entityLookup;

	protected $propertyLookup;

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

		$entityFactory = EntityFactory::singleton();
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

	public function getItem() {
		$snakList = new SnakList();

		$properties = $this->getPropertyData();

		$snakData = array(
			array( 'property' => clone $properties[0], 'value' => new EntityId( Item::ENTITY_TYPE, 42 ) ),
			array( 'property' => clone $properties[0], 'value' => new EntityId( Item::ENTITY_TYPE, 44 ) ),
			array( 'property' => clone $properties[1], 'value' => new EntityId( Item::ENTITY_TYPE, 45 ) ),
			array( 'property' => clone $properties[2], 'value' => new StringValue( 'Flag of Canada.svg' ) ),
			array( 'property' => clone $properties[3], 'value' => new StringValue( 'CA' ) ),
			array( 'property' => clone $properties[4], 'value' => new EntityId( Item::ENTITY_TYPE, 46 ) )
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

		return $item;
	}

	public function setUp() {
		parent::setUp();

		$this->entityLookup = new \Wikibase\Test\MockRepository();

		$properties = $this->getPropertyData();

		foreach( $properties as $property ) {
			$this->entityLookup->putEntity( $property );
		}

		$this->entityLookup->putEntity( $this->getItem() );

		$this->propertyLookup = new PropertySQLLookup( $this->entityLookup );
	}

	public function testConstructor() {
		$instance = new PropertySQLLookup( $this->entityLookup );
		$this->assertInstanceOf( '\Wikibase\PropertySQLLookup', $instance );
	}

	public function getMainSnaksByPropertyLabelProvider() {
		$entityId = new EntityId( Item::ENTITY_TYPE, 126 );

		return array(
			array( $entityId, 'capital', 'en', 2 ),
			array( $entityId, 'currency', 'en', 1 ),
			array( $entityId, 'president', 'en', 0 )
		);
	}

	/**
	 * @dataProvider getMainSnaksByPropertyLabelProvider
	 */
	public function testGetMainSnaksByPropertyLabel( $entityId, $propertyLabel, $langCode, $expected ) {
		$snakList = $this->propertyLookup->getMainSnaksByPropertyLabel( $entityId, $propertyLabel, $langCode );

		$this->assertInstanceOf( '\Wikibase\SnakList', $snakList );
		$this->assertEquals( $expected, $snakList->count() );
	}

	public function getPropertyLabelProvider() {
		return array(
			array( 'capital', 'en', new EntityId( Property::ENTITY_TYPE, 4 ) ),
			array( 'flag', 'en', new EntityId( Property::ENTITY_TYPE, 7 ) ),
			array( false, 'en', new EntityId( Property::ENTITY_TYPE, 10 ) )
		);
	}

	/**
	 * @depends testGetMainSnaksByPropertyLabel
	 * @dataProvider getPropertyLabelProvider
	 */
	public function testGetPropertyLabel( $expected, $lang, $id ) {
		$label = $this->propertyLookup->getPropertyLabel( $id, $lang );
		$this->assertEquals( $expected, $label );
	}
}
