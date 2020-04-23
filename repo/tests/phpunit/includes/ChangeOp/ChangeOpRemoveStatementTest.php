<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOpRemoveStatement;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpRemoveStatement
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpRemoveStatementTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ [ 'foo' ] ];
		$args[] = [ '' ];
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 */
	public function testInvalidConstruct( $guid ) {
		$this->expectException( InvalidArgumentException::class );
		new ChangeOpRemoveStatement( $guid );
	}

	public function testGetClaimGuid() {
		$guid = 'foobar';
		$changeop = new ChangeOpRemoveStatement( $guid );
		$this->assertEquals( $guid, $changeop->getGuid() );
	}

	public function changeOpProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = [];

		$item = $this->newItemWithClaim( 'q345', $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$guid = $statement->getGuid();
		$changeOp = new ChangeOpRemoveStatement( $guid );
		$expected = null;
		$args[] = [ $item, $changeOp, $expected ];

		return $args;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testApplyAddNewClaim( Item $item, ChangeOpRemoveStatement $changeOp, DataValue $expected = null ) {
		$changeOpResult = $changeOp->apply( $item );
		$this->assertNotEmpty( $changeOp->getGuid() );
		$statements = $item->getStatements();
		$this->assertEquals( $expected, $statements->getFirstStatementWithGuid( $changeOp->getGuid() ) );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
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

	public function testGetActions() {
		$changeOp = new ChangeOpRemoveStatement( 'guid' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
