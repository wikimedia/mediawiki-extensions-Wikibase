<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\StatementChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class StatementChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return StatementChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$entityId = new PropertyId( 'P7' );

		return new StatementChangeOpFactory(
			$mockProvider->getMockGuidGenerator(),
			$mockProvider->getMockGuidValidator(),
			$mockProvider->getMockGuidParser( $entityId ),
			$mockProvider->getMockSnakValidator(),
			$mockProvider->getMockSnakValidator()
		);
	}

	public function testNewSetStatementOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );
		$statement = new Statement( $snak );

		$op = $this->newChangeOpFactory()->newSetStatementOp( $statement );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveStatementOp() {
		$op = $this->newChangeOpFactory()->newRemoveStatementOp( 'DEADBEEF' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetMainSnakOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );

		$op = $this->newChangeOpFactory()->newSetMainSnakOp( 'DEADBEEF', $snak );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetQualifierOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );

		$op = $this->newChangeOpFactory()->newSetQualifierOp( 'DEADBEEF', $snak, '1337BABE' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveQualifierOp() {
		$op = $this->newChangeOpFactory()->newRemoveQualifierOp( 'DEADBEEF', '1337BABE' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetReferenceOp() {
		$reference = new Reference();

		$op = $this->newChangeOpFactory()->newSetReferenceOp( 'DEADBEEF', $reference, '1337BABE' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveReferenceOp() {
		$op = $this->newChangeOpFactory()->newRemoveReferenceOp( 'DEADBEEF', '1337BABE' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetStatementRankOp() {
		$op = $this->newChangeOpFactory()->newSetStatementRankOp( 'DEADBEEF', Statement::RANK_NORMAL );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

}
