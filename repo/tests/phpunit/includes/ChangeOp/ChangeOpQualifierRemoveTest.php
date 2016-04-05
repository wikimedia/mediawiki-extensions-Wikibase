<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpQualifierRemove;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpQualifierRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class ChangeOpQualifierRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = array( '', '' );
		$args[] = array( 'foo', '' );
		$args[] = array( '', 'foo' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $guid, $snakHash ) {
		new ChangeOpQualifierRemove( $guid, $snakHash );
	}

	public function changeOpRemoveProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = [];

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$newQualifier = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$statement->getQualifiers()->addSnak( $newQualifier );
		$snakHash = $newQualifier->getHash();
		$changeOp = new ChangeOpQualifierRemove( $guid, $snakHash );
		$args[] = array( $item, $changeOp, $snakHash );

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 */
	public function testApplyRemoveQualifier( Item $item, ChangeOpQualifierRemove $changeOp, $snakHash ) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$qualifiers = $statement->getQualifiers();
		$this->assertFalse( $qualifiers->hasSnakHash( $snakHash ), "Qualifier still exists" );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$itemIdString . '$D8404CDA-25E4-4334-AG03-A3290BCD9CQP'
		);

		return $item;
	}

}
