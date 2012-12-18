<?php

namespace Wikibase\Lib\Test;
use Wikibase\Claims;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\ClaimObject;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;
use Wikibase\SnakList;
use DataValues\StringValue;
use Wikibase\CachingEntityLoader;
use Wikibase\LibRegistry;
use Wikibase\Settings;

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
 */
class ReferencedEntitiesFinderTest extends \MediaWikiTestCase {

	public function claimsProvider() {
		$argLists = array();

		$argLists[] = array( array(), array() );

		$argLists[] = array(
			array(
				new ClaimObject( new PropertyNoValueSnak( 42 ) )
			),
			array(
				new EntityId( Property::ENTITY_TYPE, 42 )
			)
		);

		$argLists[] = array(
			array(
				new ClaimObject( new PropertyNoValueSnak( 42 ) ),
				new ClaimObject( new PropertyNoValueSnak( 43 ) ),
			),
			array(
				new EntityId( Property::ENTITY_TYPE, 42 ),
				new EntityId( Property::ENTITY_TYPE, 43 ),
			)
		);

		$argLists[] = array(
			array(
				new ClaimObject(
					new PropertyNoValueSnak( 42 ),
					new SnakList( array(
						new PropertyNoValueSnak( 42 ),
						new PropertySomeValueSnak( 43 ),
						new PropertyValueSnak( 1, new StringValue( 'onoez' ) ),
					) )
				),
				new ClaimObject( new PropertyNoValueSnak( 44 ) ),
			),
			array(
				new EntityId( Property::ENTITY_TYPE, 42 ),
				new EntityId( Property::ENTITY_TYPE, 43 ),
				new EntityId( Property::ENTITY_TYPE, 44 ),
				new EntityId( Property::ENTITY_TYPE, 1 ),
			)
		);

		$id9001 = new EntityId( Item::ENTITY_TYPE, 9001 );
		$id1 = new EntityId( Item::ENTITY_TYPE, 1 );

		$argLists[] = array(
			array(
				new ClaimObject(
					new PropertyValueSnak( 2, new StringValue( $id9001->getPrefixedId() ) ),
					new SnakList( array(
						new PropertyNoValueSnak( 42 ),
						new PropertySomeValueSnak( 43 ),
						new PropertyValueSnak( 1, new StringValue( 'onoez' ) ),
						new PropertyValueSnak( 2, new StringValue( $id1->getPrefixedId() ) ),
					) )
				),
				new ClaimObject( new PropertyNoValueSnak( 44 ) ),
			),
			array(
				new EntityId( Property::ENTITY_TYPE, 2 ),
				new EntityId( Property::ENTITY_TYPE, 42 ),
				new EntityId( Property::ENTITY_TYPE, 43 ),
				new EntityId( Property::ENTITY_TYPE, 44 ),
				new EntityId( Property::ENTITY_TYPE, 1 ),
				new EntityId( Item::ENTITY_TYPE, 9001 ),
				new EntityId( Item::ENTITY_TYPE, 1 ),
			)
		);

		return $argLists;
	}

	/**
	 * @dataProvider claimsProvider
	 *
	 * @param Claim[] $claims
	 * @param EntityId[] $expected
	 */
	public function testFindClaimLinks( array $claims, array $expected ) {
		$linkFinder = new ReferencedEntitiesFinder( $this->getMockEntityLoader() );

		$actual = $linkFinder->findClaimLinks( new Claims( $claims ) );

		$this->assertArrayEquals( $expected, $actual );
	}

	/**
	 * @return \Wikibase\EntityLookup
	 */
	protected function getMockEntityLoader() {
		$entityLoader = new CachingEntityLoader();

		$libRegistry = new LibRegistry( Settings::singleton() );
		$dataTypeFactory = $libRegistry->getDataTypeFactory();

		$stringProp = Property::newEmpty();
		$stringProp->setId( 1 );
		$stringProp->setDataType( $dataTypeFactory->getType( 'commonsMedia' ) );

		$itemProp = Property::newEmpty();
		$itemProp->setId( 2 );
		$itemProp->setDataType( $dataTypeFactory->getType( 'wikibase-item' ) );

		$entityLoader->setEntities( array( $stringProp, $itemProp ) );

		return $entityLoader;
	}

}
