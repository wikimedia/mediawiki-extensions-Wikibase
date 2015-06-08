<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpStatementRemove;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpStatementRemove
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpStatementRemoveTest extends \PHPUnit_Framework_TestCase {

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( array( 'foo' ) );
		$args[] = array( '' );
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct( $claimGuid ) {
		new ChangeOpStatementRemove( $claimGuid );
	}

	public function testGetClaimGuid() {
		$claimguid = 'foobar';
		$changeop = new ChangeOpStatementRemove( $claimguid );
		$this->assertEquals( $claimguid, $changeop->getStatementGuid() );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$changeOp = new ChangeOpStatementRemove( $guid );
		$expected = null;
		$args[] = array ( $item, $changeOp, $expected );

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testApplyAddNewClaim( Item $item, ChangeOpStatementRemove $changeOp, DataValue $expected = null ) {
		$this->assertTrue( $changeOp->apply( $item ), "Applying the ChangeOp did not return true" );
		$this->assertNotEmpty( $changeOp->getStatementGuid() );
		$claims = new Claims( $item->getClaims() );
		$this->assertEquals( $expected, $claims->getClaimWithGuid( $changeOp->getStatementGuid() ) );
	}

	private function newItemWithClaim( $itemIdString, $snak ) {
		$item = new Item( new ItemId( $itemIdString ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P'
		);

		return $item;
	}

}
