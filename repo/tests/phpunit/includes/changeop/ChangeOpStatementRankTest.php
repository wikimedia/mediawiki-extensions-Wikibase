<?php

namespace Wikibase\Test;

use Wikibase\Claims;
use Wikibase\ChangeOpStatementRank;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpStatementRank
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
class ChangeOpStatementRankTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$validIdFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$validRank = 1;

		$args = array();
		$args[] = array( 123, $validRank, $validIdFormatter );
		$args[] = array( $validClaimGuid, ':-)', $validIdFormatter );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $rank, $idFormatter ) {
		$ChangeOpStatementRank = new ChangeOpStatementRank( $claimGuid, $rank, $idFormatter );
	}

	public function changeOpProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$rank = 1;

		$changeOp = new ChangeOpStatementRank( $claimGuid, $rank, $idFormatter );

		$args[] = array ( $item, $changeOp, $rank );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpStatementRank $changeOp
	 */
	public function testApplyStatementRank( $item, $changeOp, $expectedRank ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$rank = $claims[0]->getRank();
		$this->assertEquals( $rank, $expectedRank, "No reference with expected hash" );
	}

	protected function provideNewItemWithClaim( $itemId, $snak ) {
		$entity = ItemContent::newFromArray( array( 'entity' => $itemId ) )->getEntity();
		$claim = $entity->newClaim( $snak );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );

		return $entity;
	}
}
