<?php

namespace Wikibase\Test;

use Wikibase\EntityContentFactory;

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

	public function testGetEntityContentModels() {
		$contentModelIds = array( 42, 1337, 9001 );

		$factory = new EntityContentFactory(
			$this->newMockIdFormatter(),
			$contentModelIds
		);

		$this->assertEquals( $contentModelIds, $factory->getEntityContentModels() );
	}

	protected function newMockIdFormatter() {
		$idFormatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()->getMock();

		return $idFormatter;
	}

	public function testIsEntityContentModel() {
		$factory = new EntityContentFactory(
			$this->newMockIdFormatter(),
			array( 42, 1337, 9001 )
		);

		foreach ( $factory->getEntityContentModels() as $type ) {
			$this->assertTrue( $factory->isEntityContentModel( $type ) );
		}

		$this->assertFalse( $factory->isEntityContentModel( 'this-does-not-exist' ) );
	}

}
