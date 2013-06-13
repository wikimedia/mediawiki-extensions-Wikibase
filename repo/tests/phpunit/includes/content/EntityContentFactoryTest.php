<?php

namespace Wikibase\Test;

use Wikibase\EntityContentFactory;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Property;

/**
 * @covers Wikibase\EntityContentFactory
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
 * @since 0.2
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider contentModelsProvider
	 */
	public function testGetEntityContentModels( array $contentModelIds ) {
		$factory = new EntityContentFactory(
			$this->newMockIdFormatter(),
			$contentModelIds
		);

		$this->assertEquals( $contentModelIds, $factory->getEntityContentModels() );
	}

	protected function newMockIdFormatter() {
		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()->getMock();

		$idFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'Nyan' ) );

		return $idFormatter;
	}

	public function contentModelsProvider() {
		$argLists = array();

		$argLists[] = array( array() );
		$argLists[] = array( array( 0 ) );
		$argLists[] = array( array( 42, 1337, 9001 ) );
		$argLists[] = array( array( 0, 1, 2, 3, 4, 5, 6, 7 ) );

		return $argLists;
	}

	public function testIsEntityContentModel() {
		$factory = $this->newFactory();

		foreach ( $factory->getEntityContentModels() as $type ) {
			$this->assertTrue( $factory->isEntityContentModel( $type ) );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );
	}

	protected function newFactory() {
		return $factory = new EntityContentFactory(
			$this->newMockIdFormatter(),
			array( 42, 1337, 9001 )
		);
	}

	public function testGetTitleForId() {
		$factory = $this->newFactory();

		$title = $factory->getTitleForId( new EntityId( 'item', 42 ) );

		$this->assertEquals( 'Nyan', $title->getText() );
	}

	public function testGetWikiPageForId() {
		$entityId = new EntityId( 'item', 42 );

		$factory = $this->newFactory();

		$expectedTitle = $factory->getTitleForId( $entityId );

		$wikiPage = $factory->getWikiPageForId( $entityId );

		$this->assertEquals( $expectedTitle, $wikiPage->getTitle() );
	}

	public function entityTypesProvider() {
		$argLists = array();
	
		$argLists[] = array( Item::ENTITY_TYPE );
		$argLists[] = array( Property::ENTITY_TYPE );

		return $argLists;
	}

	public function invalidEntityTypesProvider() {
		$argLists = array();

		$argLists[] = array( 42 );
		$argLists[] = array( 'foo' );

		return $argLists;
	}

	/**
	 * @dataProvider entityTypesProvider
	 */
	public function testNewFromType( $type ) {
		$entityContentFactory = EntityContentFactory::singleton();
		$entityContent = $entityContentFactory->newFromType( $type );
		$this->assertEquals( $type, $entityContent->getEntity()->getType() );
	}

	/**
	 * @dataProvider invalidEntityTypesProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidNewFromType( $type ) {
		$entityContentFactory = EntityContentFactory::singleton();
		$entityContent = $entityContentFactory->newFromType( $type );
	}

}
