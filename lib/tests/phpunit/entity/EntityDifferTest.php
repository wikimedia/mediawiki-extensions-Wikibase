<?php

namespace Wikibase\Test;
use Wikibase\Entity;
use Wikibase\EntityDiff;
use Wikibase\EntityDiffer;

/**
 * Tests for the Wikibase\EntityDiff deriving classes.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseDiff
 * @group EntityDifferTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDifferTest extends \MediaWikiTestCase {

	public function diffProvider() {
		$argLists = array();

		$entity0 = \Wikibase\Item::newEmpty();
		$entity1 = \Wikibase\Item::newEmpty();
		$expected = new \Wikibase\ItemDiff();

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = \Wikibase\Property::newEmpty();
		$entity1 = \Wikibase\Property::newEmpty();
		$expected = new \Wikibase\EntityDiff();

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = \Wikibase\Property::newEmpty();
		$entity1 = \Wikibase\Property::newEmpty();
		$expected = new \Wikibase\EntityDiff();

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = \Wikibase\Property::newEmpty();
		$entity0->addAliases( 'nl', array( 'bah' ) );
		$entity0->addAliases( 'de', array( 'bah' ) );

		$entity1 = \Wikibase\Property::newEmpty();
		$entity1->addAliases( 'en', array( 'foo', 'bar' ) );
		$entity1->addAliases( 'nl', array( 'bah', 'baz' ) );

		$entity1->setDescription( 'en', 'onoez' );

		$expected = new \Wikibase\EntityDiff( array(
			'aliases' => new \Diff\Diff( array(
				'en' => new \Diff\Diff( array(
					new \Diff\DiffOpAdd( 'foo' ),
					new \Diff\DiffOpAdd( 'bar' ),
				), false ),
				'de' => new \Diff\Diff( array(
					new \Diff\DiffOpRemove( 'bah' ),
				), false ),
				'nl' => new \Diff\Diff( array(
					new \Diff\DiffOpAdd( 'baz' ),
				), false )
			) ),
			'description' => new \Diff\Diff( array(
				'en' => new \Diff\DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = \Wikibase\Item::newEmpty();

		$entity1 = \Wikibase\Item::newEmpty();
		$entity1->setLabel( 'en', 'onoez' );

		$expected = new \Wikibase\ItemDiff( array(
			'label' => new \Diff\Diff( array(
				'en' => new \Diff\DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 *
	 * @param Entity $entity0
	 * @param Entity $entity1
	 * @param EntityDiff $expected
	 */
	public function testDiffEntities( Entity $entity0, Entity $entity1, EntityDiff $expected ) {
		$differ = EntityDiffer::newForType( $entity0->getType() );

		$actual = $differ->diffEntities( $entity0, $entity1 );

		$this->assertInstanceOf( '\Wikibase\EntityDiff', $actual );
		$this->assertEquals( $expected, $actual );
		$this->assertArrayEquals( iterator_to_array( $expected ), iterator_to_array( $actual ), false, true );
	}

}