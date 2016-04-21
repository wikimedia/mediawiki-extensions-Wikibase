<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReferenceRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = array( '', '' );
		$args[] = array( '', 'foo' );
		$args[] = array( 'foo', '' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $guid, $referenceHash ) {
		new ChangeOpReferenceRemove( $guid, $referenceHash );
	}

	public function testApplyRemovesReference() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( array( $referenceSnak ) );

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		$changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->isEmpty() );
	}

	public function testApplyWithDuplicateReferencePreservesOne() {
		$mainSnak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$referenceSnak = new PropertyValueSnak( 78462378, new StringValue( 'newReference' ) );
		$reference = new Reference( array( $referenceSnak ) );
		$referenceHash = $reference->getHash();

		$item = $this->newItemWithClaim( 'q345', $mainSnak );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( clone $reference );
		$statement->getReferences()->addReference( clone $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $referenceHash );

		$changeOp->apply( $item );

		$newStatement = $item->getStatements()->toArray()[0];
		$this->assertTrue( $newStatement->getReferences()->hasReferenceHash( $referenceHash ) );
		$this->assertCount( 1, $newStatement->getReferences() );
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

}
