<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\ChangeOp\ClaimChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ClaimChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return ClaimChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		$entityId = new PropertyId( 'P7' );

		return new ClaimChangeOpFactory(
			$mockProvider->getMockGuidGenerator(),
			$mockProvider->getMockGuidValidator(),
			$mockProvider->getMockGuidParser( $entityId ),
			$mockProvider->getMockSnakValidator()
		);
	}

	public function testNewAddClaimOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );
		$claim = new Claim( $snak );

		$op = $this->newChangeOpFactory()->newAddClaimOp( $claim );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetClaimOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );
		$claim = new Claim( $snak );

		$op = $this->newChangeOpFactory()->newSetClaimOp( $claim );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveClaimOp() {
		$op = $this->newChangeOpFactory()->newRemoveClaimOp( 'DEADBEEF' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetMainSnakOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );

		$op = $this->newChangeOpFactory()->newSetMainSnakOp( 'DEADBEEF', $snak );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetQualifierOp() {
		$snak = new PropertyNoValueSnak( new PropertyId( 'P7' ) );

		$op = $this->newChangeOpFactory()->newSetQualifierOp( 'DEADBEEF', $snak, '1337BABE' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveQualifierOp() {
		$op = $this->newChangeOpFactory()->newRemoveQualifierOp( 'DEADBEEF', '1337BABE' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

}
