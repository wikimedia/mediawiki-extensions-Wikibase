<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpClaim;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Entity;
use Wikibase\ItemContent;
use InvalidArgumentException;
use Wikibase\PropertyNoValueSnak;
use Wikibase\SnakObject;

/**
 * @covers Wikibase\ChangeOpClaim
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
 * @author Adam Shorland
 */
class ChangeOpClaimTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( 42, 'add' );
		$args[] = array( 'en', 'remove' );
		$args[] = array( array(), 'remove' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param Claim $claim
	 * @param string $action
	 */
	public function testInvalidConstruct( $claim, $action ) {
		$changeOp = new ChangeOpClaim( $claim, $action );
	}

	public function changeOpClaimProvider() {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$noValueClaim = new Claim( new PropertyNoValueSnak( 43 ) );
		$args = array();

		$args[] = array (
			clone $entity,
			new ChangeOpClaim( new Claim( new PropertyNoValueSnak( 43 ) ), 'add' ),
			array( clone $noValueClaim ),
		);
		$args[] = array (
			clone $entity,
			new ChangeOpClaim( new Claim( new PropertyNoValueSnak( 43 ) ), 'remove' ),
			array(),
		);

		return $args;
	}

	/**
	 * @dataProvider changeOpClaimProvider
	 *
	 * @param Entity $entity
	 * @param ChangeOpClaim $changeOpClaim
	 * @param Claim[] $expectedClaims
	 */
	public function testApply( $entity, $changeOpClaim, $expectedClaims ) {
		$changeOpClaim->apply( $entity );
		$entityClaims = new Claims( $entity->getClaims() );
		foreach( $expectedClaims as $claim ){
			$this->assertTrue( $entityClaims->hasClaim( $claim ) );
		}
		$this->assertEquals( count( $expectedClaims ), $entityClaims->count() );
	}

	/**
	 * @expectedException \Wikibase\ChangeOpException
	 */
	public function testApplyWithInvalidAction() {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$changeOpClaim = new ChangeOpClaim( new Claim( new PropertyNoValueSnak( 43 ) ) , 'invalidAction'  );
		$changeOpClaim->apply( $entity );
	}

}
