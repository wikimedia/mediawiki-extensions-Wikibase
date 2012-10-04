<?php

namespace Wikibase\Test;
use Wikibase\EntityFactory;

/**
 * Tests for the Wikibase\EntityFactory class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityFactoryTest extends \MediaWikiTestCase {

	public function testGetEntityTypes() {
		$this->assertInternalType( 'array', EntityFactory::singleton()->getEntityTypes() );
	}

	public function testIsEntityType() {
		foreach ( EntityFactory::singleton()->getEntityTypes() as $type ) {
			$this->assertTrue( EntityFactory::singleton()->isEntityType( $type ) );
		}

		$this->assertFalse( EntityFactory::singleton()->isEntityType( 'this-does-not-exist' ) );
	}

}
