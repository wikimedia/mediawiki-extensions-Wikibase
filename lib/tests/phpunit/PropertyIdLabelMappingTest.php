<?php

namespace Wikibase\Test;
use Wikibase\PropertyIdLabelMapping;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\Property;

/**
 * Tests for the Wikibase\PropertyIdLabelMapping class.
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
 * @group PropertyIdLabelMappingTest
 *
 * @licence GNU GPL v2+
 * @author Katie FIlbert < aude.wiki@gmail.com >
 */
class PropertyIdLabelMappingTest extends \MediaWikiTestCase {

	public function propertyProvider() {
		$propertyData = array(
			4 => 'capital',
			6 => 'mayor',
			7 => 'country',
			10 => 'flag',
			8 => 'seal',
			1 => 'website'
		);

		$entityFactory = EntityFactory::singleton();
		$properties = array();
		$langCode = 'en';

		foreach( $propertyData as $numericId => $label ) {
			$property = Property::newEmpty();
			$property->setId( new EntityId( Property::ENTITY_TYPE, $numericId ) );
			$property->setLabel( $langCode, $label );
			$properties[] = $property;
		}

		return $properties;
	}

	public function constructorProvider() {
		return array(
			array( 'en' ),
			array( 'de' ),
			array( 'es' )
		);
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $langCode ) {
		$instance = new PropertyIdLabelMapping( $langCode );
		$this->assertInstanceOf( '\Wikibase\PropertyIdLabelMapping', $instance );
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testGetLanguageCode( $langCode ) {
		$instance = new PropertyIdLabelMapping( $langCode );
		$this->assertEquals( $langCode, $instance->getLanguageCode() );
	}

	public function setPropertyProvider() {
		$properties = $this->propertyProvider();

		return array(
			array( 6, $properties, 'en' )
		);
	}

	/**
	 * @dataProvider setPropertyProvider
	 */
	public function testSetProperty( $expected, $properties, $langCode ) {
		$instance = new PropertyIdLabelMapping( $langCode );

		foreach( $properties as $property ) {
			$numericId = $property->getId()->getNumericId();
			$instance->setProperty( $numericId, $property->getLabel( $langCode ) );
			$this->assertTrue( $instance->offsetExists( $numericId ) );
		}

		$this->assertEquals( $expected, $instance->count() );
	}

	public function hasPropertyProvider() {
		$properties = $this->propertyProvider();

		return array(
			array( $properties, 'en' )
		);
	}

	/**
	 * @dataProvider hasPropertyProvider
	 */
	public function testHasProperty( $properties, $langCode ) {
		$instance = new PropertyIdLabelMapping( $langCode );

		foreach( $properties as $property ) {
			$numericId = $property->getId()->getNumericId();
			$instance->setProperty( $numericId, $property->getLabel( $langCode ) );

			$this->assertTrue( $instance->hasProperty( $numericId ) );
		}
	}

	public function getByLabelProvider() {
		$properties = $this->propertyProvider();

		return array(
			array( array( 'id' => 6, 'label' => 'mayor' ), $properties, 'en' ),
			array( array( 'id' => 10, 'label' => 'flag' ), $properties, 'en' )
		);
	}

	/**
	 * @dataProvider getByLabelProvider
	 */
	public function testGetByLabel( $search, $properties, $langCode ) {
		$instance = new PropertyIdLabelMapping( $langCode );

		foreach( $properties as $property ) {
			$numericId = $property->getId()->getNumericId();
			$label = $property->getLabel( $langCode );
			$instance->setProperty( $numericId, $label );
		}

		$matches = $instance->getByLabel( $search['label'] );

		$this->assertEquals( $search['id'], $matches[0] );
	}

}
