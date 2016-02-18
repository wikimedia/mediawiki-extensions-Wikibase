<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpReferenceRemove;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReferenceRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Addshore
 */
class ChangeOpReferenceRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
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
		$item = $this->newItemWithClaim( 'q345', new PropertyValueSnak( 2754236, new StringValue( 'test' ) ) );
		$reference = new Reference( array( new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) ) ) );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		$changeOp->apply( $item );

		$this->assertTrue( $statement->getReferences()->isEmpty() );
	}

	public function testApplyWithDuplicateReferencePreservesOne() {
		$item = $this->newItemWithClaim( 'q345', new PropertyValueSnak( 2754236, new StringValue( 'test' ) ) );
		$reference = new Reference( array( new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) ) ) );
		$statement = $item->getStatements()->toArray()[0];
		$statement->getReferences()->addReference( clone $reference );
		$statement->getReferences()->addReference( clone $reference );
		$changeOp = new ChangeOpReferenceRemove( $statement->getGuid(), $reference->getHash() );

		$changeOp->apply( $item );

		$this->assertTrue( $statement->getReferences()->hasReferenceHash( $reference->getHash() ) );
		$this->assertCount( 1, $statement->getReferences() );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
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
