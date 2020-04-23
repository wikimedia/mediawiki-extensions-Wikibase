<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOpQualifierRemove;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpQualifierRemove
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ChangeOpQualifierRemoveTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructorProvider() {
		$args = [];
		$args[] = [ '', '' ];
		$args[] = [ 'foo', '' ];
		$args[] = [ '', 'foo' ];
		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 */
	public function testInvalidConstruct( $guid, $snakHash ) {
		$this->expectException( InvalidArgumentException::class );
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
		$args[] = [ $item, $changeOp, $snakHash ];

		return $args;
	}

	/**
	 * @dataProvider changeOpRemoveProvider
	 */
	public function testApplyRemoveQualifier( Item $item, ChangeOpQualifierRemove $changeOp, $snakHash ) {
		$changeOpResult = $changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$qualifiers = $statement->getQualifiers();
		$this->assertFalse( $qualifiers->hasSnakHash( $snakHash ), "Qualifier still exists" );
		$this->assertTrue( $changeOpResult->isEntityChanged() );
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

	public function testGetActions() {
		$changeOp = new ChangeOpQualifierRemove( 'guid', 'snakhash' );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT ], $changeOp->getActions() );
	}

}
