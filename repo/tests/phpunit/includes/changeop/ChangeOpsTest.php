<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpLabel;
use Wikibase\ChangeOpDescription;
use Wikibase\ChangeOps;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ChangeOps
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
class ChangeOpsTest extends \PHPUnit_Framework_TestCase {

	public function testEmptyChangeOps() {
		$changeOps = new ChangeOps();
		$this->assertEmpty( $changeOps->getChangeOps() );
	}

	/**
	 * @return \Wikibase\ChangeOp[]
	 */
	public function changeOpProvider() {
		$ops = array();
		$ops[] = array ( new ChangeOpLabel( 'en', 'myNewLabel' ) );
		$ops[] = array ( new ChangeOpDescription( 'de', 'myNewDescription' ) );
		$ops[] = array ( new ChangeOpLabel( 'en', null ) );

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param ChangeOp $changeOp
	 */
	public function testAdd( $changeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( array( $changeOp ), $changeOps->getChangeOps() );
	}

	public function changeOpArrayProvider() {
		$ops = array();
		$ops[] = array (
					array(
						new ChangeOpLabel( 'en', 'enLabel' ),
						new ChangeOpLabel( 'de', 'deLabel' ),
						new ChangeOpDescription( 'en', 'enDescr' ),
					)
				);

		return $ops;
	}

	/**
	 * @dataProvider changeOpArrayProvider
	 *
	 * @param ChangeOp[] $changeOp
	 */
	public function testAddArray( $changeOpArray ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOpArray );
		$this->assertEquals( $changeOpArray, $changeOps->getChangeOps() );
	}

	public function invalidChangeOpProvider() {
		$ops = array();
		$ops[] = array ( 1234 );
		$ops[] = array ( array( new ChangeOpLabel( 'en', 'test' ), 123 ) );

		return $ops;
	}

	/**
	 * @dataProvider invalidChangeOpProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param $invalidChangeOp
	 */
	public function testInvalidAdd( $invalidChangeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $invalidChangeOp );
	}

	public function changeOpsProvider() {
		$args = array();

		$language = 'en';
		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( $language, 'newLabel' ) );
		$changeOps->add( new ChangeOpDescription( $language, 'newDescription' ) );
		$args[] = array( $changeOps, $language, 'newLabel', 'newDescription' );

		return $args;
	}

	/**
	 * @dataProvider changeOpsProvider
	 *
	 * @param ChangeOps $changeOps
	 * @param string $language
	 * @param string $expectedLabel
	 * @param string $expectedDescription
	 */
	public function testApply( $changeOps, $language, $expectedLabel, $expectedDescription ) {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getLabel( $language ) );
		$this->assertEquals( $expectedDescription, $entity->getDescription( $language ) );
	}

}
