<?php

namespace Wikibase\Test;

use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Diff\DiffOpAdd;
use Wikibase\ChangeOpDescription;
use Wikibase\ItemContent;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpDescription
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpDescriptionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		$changeOpDescription = new ChangeOpDescription( 42, new DiffOpChange( 'myOld', 'myNew' ) );
	}

	public function changeOpDescriptionProvider() {
		$args = array();
		$args[] = array ( new ChangeOpDescription( 'en', new DiffOpChange( 'myOld', 'myNew' ) ), 'myNew' );
		$args[] = array ( new ChangeOpDescription( 'en', new DiffOpRemove( 'myOld' ) ), '' );
		$args[] = array ( new ChangeOpDescription( 'en', new DiffOpAdd( 'myNew' ) ), 'myNew' );

		return $args;
	}

	/**
	 * @dataProvider changeOpDescriptionProvider
	 *
	 * @param ChangeOpDescription $changeOpDescription
	 * @param string $expectedDescription
	 */
	public function testApply( $changeOpDescription, $expectedDescription ) {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$entity->setDescription( 'en', 'test' );

		$changeOpDescription->apply( $entity );

		$this->assertEquals( $expectedDescription, $entity->getDescription( 'en' ) );
	}

}
