<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Repo\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpReferenceRemove
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ '', '' ];
		$args[] = [ '', 'foo' ];
		$args[] = [ 'foo', '' ];
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 */
	public function testInvalidConstruct( $guid, $referenceHash ) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpReferenceRemove( $guid, $referenceHash );
	}

	public function testApplyRemovesReference() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		$changeOpResult = $changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->isEmpty() );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function testApplyWithDuplicateReferencePreservesOne() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( [ $referenceSnak ] );
		$referenceHash = $reference->getHash();

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( clone $reference );
		$statement->getReferences()->addReference( clone $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $referenceHash );

		$changeOpResult = $changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->hasReferenceHash( $referenceHash ) );
		$this->assertCount( 1, $newStatement->getReferences() );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	private function newItemWithClaim( $itemIdString, Snak $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP'
		);

		return $item;
	}

	public function testGetActions() {
		$changeOp = new ChangeOpReferenceRemove( 'guid', 'refhash' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
