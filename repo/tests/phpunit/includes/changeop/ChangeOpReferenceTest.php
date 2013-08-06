<?php

namespace Wikibase\Test;

use Wikibase\Claims;
use Wikibase\ChangeOpReference;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Reference;
use Wikibase\SnakList;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOpReference
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
class ChangeOpReferenceTest extends \PHPUnit_Framework_TestCase {

	public function invalidArgumentProvider() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$validIdFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
		$validClaimGuid = $guidGenerator->newGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );
		$validReference = new Reference( $snaks );
		$validReferenceHash = $validReference->getHash();

		$args = array();
		$args[] = array( 123, $validReference, $validReferenceHash, $validIdFormatter );
		$args[] = array( '', $validReference, $validReferenceHash, $validIdFormatter );
		$args[] = array( '', null, $validReferenceHash, $validIdFormatter );
		$args[] = array( $validClaimGuid, $validReference, 123, $validIdFormatter );
		$args[] = array( $validClaimGuid, 'notAReference', $validReferenceHash, $validIdFormatter );
		$args[] = array( $validClaimGuid, 'notAReference', '', $validIdFormatter );
		$args[] = array( $validClaimGuid, null, '', $validIdFormatter );

		return $args;
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid, $reference, $referenceHash, $idFormatter ) {
		$ChangeOpQualifier = new ChangeOpReference( $claimGuid, $reference, $referenceHash, $idFormatter );
	}

	public function changeOpAddProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $newReference, '', $idFormatter );
		$referenceHash = $newReference->getHash();
		$args[] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyAddNewReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
	}

	public function changeOpRemoveProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q345', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claims[0]->getReferences();
		$references->addReference( $newReference );
		$claims[0]->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReference( $claimGuid, null, $referenceHash, $idFormatter );
		$args[] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplyRemoveReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertFalse( $references->hasReferenceHash( $referenceHash ), "Reference still exists" );
	}

	public function changeOpSetProvider() {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$args = array();

		$item = $this->provideNewItemWithClaim( 'q123', $snak );
		$claims = $item->getClaims();
		$claimGuid = $claims[0]->getGuid();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $claims[0]->getReferences();
		$references->addReference( $newReference );
		$claims[0]->setReferences( $references );
		$item->setClaims( new Claims( $claims ) );
		$referenceHash = $newReference->getHash();
		$snaks = new SnakList();
		$snaks[] = new \Wikibase\PropertyValueSnak( 78462378, new \DataValues\StringValue( 'changedQualifier' ) );
		$changedReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference( $claimGuid, $changedReference, $referenceHash, $idFormatter );
		$args[] = array ( $item, $changeOp, $changedReference->getHash() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 *
	 * @param Entity $item
	 * @param ChangeOpReference $changeOp
	 * @param string $referenceHash
	 */
	public function testApplySetReference( $item, $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$claims = new Claims( $item->getClaims() );
		$references = $claims[0]->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), "No reference with expected hash" );
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
