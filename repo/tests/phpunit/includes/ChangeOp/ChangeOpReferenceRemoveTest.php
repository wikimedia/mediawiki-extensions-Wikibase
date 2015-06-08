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
 * @author Adam Shorland
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

	public function changeOpRemoveProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$statement->getReferences()->addReference( $newReference );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReferenceRemove( $guid, $referenceHash );
		$args[ 'Removing a single reference' ] = array ( $item, $changeOp, $referenceHash );

		$item = $this->newItemWithClaim( 'q346', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$references = $statement->getReferences();
		$references->addReference( $newReference );
		$references->addReference( $newReference );
		$referenceHash = $newReference->getHash();
		$changeOp = new ChangeOpReferenceRemove( $guid, $referenceHash );
		$args[ 'Removing references that have the same hash' ] = array ( $item, $changeOp, $referenceHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 */
	public function testApplyRemoveReference( Item $item, ChangeOpReferenceRemove $changeOp, $referenceHash ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$statements = $item->getStatements()->toArray();
		$this->assertCount( 1, $statements, 'More than one claim returned on item...' );
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertFalse( $references->hasReferenceHash( $referenceHash ), "Reference still exists" );
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
