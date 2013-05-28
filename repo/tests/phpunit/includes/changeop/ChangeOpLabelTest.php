<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpLabel;
use Wikibase\ItemContent;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpLabel
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
class ChangeOpLabelTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		$changeOpLabel = new ChangeOpLabel( 42, 'myNew' );
	}

	public function changeOpLabelProvider() {
		$args = array();
		$args[] = array ( new ChangeOpLabel( 'en', 'myNew' ), 'myNew' );
		$args[] = array ( new ChangeOpLabel( 'en', null ), '' );

		return $args;
	}

	/**
	 * @dataProvider changeOpLabelProvider
	 *
	 * @param ChangeOpLabel $changeOpLabel
	 * @param string $expectedLabel
	 */
	public function testApply( $changeOpLabel, $expectedLabel ) {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$entity->setLabel( 'en', 'test' );

		$changeOpLabel->apply( $entity );

		$this->assertEquals( $expectedLabel, $entity->getLabel( 'en' ) );
	}

}
