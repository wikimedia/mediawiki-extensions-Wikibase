<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\ChangeOp\StatementChangeOpFactory
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class StatementChangeOpFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return StatementChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$entityId = new NumericPropertyId( 'P7' );

		return new StatementChangeOpFactory(
			new GuidGenerator(),
			$mockProvider->getMockGuidValidator(),
			$mockProvider->getMockGuidParser( $entityId ),
			$mockProvider->getMockSnakValidator(),
			$mockProvider->getMockSnakValidator(),
			$mockProvider->getMockSnakNormalizer(),
			$mockProvider->getMockReferenceNormalizer(),
			$mockProvider->getMockStatementNormalizer(),
			true
		);
	}

	public function testNewSetStatementOp() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P7' ) );
		$statement = new Statement( $snak );

		$op = $this->newChangeOpFactory()->newSetStatementOp( $statement );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveStatementOp() {
		$op = $this->newChangeOpFactory()->newRemoveStatementOp( 'DEADBEEF' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetMainSnakOp() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P7' ) );

		$op = $this->newChangeOpFactory()->newSetMainSnakOp( 'DEADBEEF', $snak );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetQualifierOp() {
		$snak = new PropertyNoValueSnak( new NumericPropertyId( 'P7' ) );

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

	public function testNormalize(): void {
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'a string' ) );

		$op = $this->newChangeOpFactory()->newSetMainSnakOp( 'DEADBEEF', $snak );

		$this->assertInstanceOf( ChangeOpMainSnak::class, $op );
		$value = TestingAccessWrapper::newFromObject( $op )->snak->getDataValue();
		$this->assertInstanceOf( StringValue::class, $value );
		$this->assertSame( 'A STRING', $value->getValue() );
	}

}
