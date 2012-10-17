<?php

namespace Wikibase\Test;
use Wikibase\EntityFactory;

/**
 * Tests for the isPrefixedId method in the Wikibase\EntityFactory class.
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
 * @group EntityFactoryIsPrefixedIdTest
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityFactoryIsPrefixedIdTest extends EntityFactoryTest {

	public function provideIsPrefixed() {
		$data = array();
		$numbers = array( '0', '1', '23', '456', '7890' );
		$prefixes = array(
			\Wikibase\ItemObject::getIdPrefix(),
			\Wikibase\PropertyObject::getIdPrefix(),
			\Wikibase\QueryObject::getIdPrefix()
		);
		foreach ( $prefixes as $prefix ) {
			foreach ( $numbers as $num ) {
				$data[] = array( "{$num}", false );
				$data[] = array( "{$prefix}", false );
				$data[] = array( "{$prefix}{$num}", true );
				$data[] = array( "{$num}{$prefix}", false );
			}
		}
		return $data;
	}

	/**
	 * @dataProvider provideIsPrefixed
	 */
	public function testIsPrefixed( $id, $expect ) {
		$this->assertEquals( $expect, EntityFactory::singleton()->isPrefixedId( $id ) );
	}

}