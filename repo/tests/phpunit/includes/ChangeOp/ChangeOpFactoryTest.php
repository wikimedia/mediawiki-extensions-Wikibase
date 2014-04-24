<?php

namespace Wikibase\Test;
use Wikibase\ChangeOp\ChangeOpFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\ChangeOp\ChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	protected $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	/**
	 * @return ChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$entityId = new PropertyId( 'P7' );

		return new ChangeOpFactory(
			Property::ENTITY_TYPE,
			$this->mockProvider->getMockLabelDescriptionDuplicateDetector(),
			$this->mockProvider->getMockSitelinkCache(),
			$this->mockProvider->getMockGuidGenerator(),
			$this->mockProvider->getMockGuidValidator(),
			$this->mockProvider->getMockGuidParser( $entityId ),
			$this->mockProvider->getMockSnakValidator()
		);
	}

	public function testNewAddAliasesOp() {
		$op = $this->newChangeOpFactory()->newAddAliasesOp( 'en', array( 'foo' ) );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetAliasesOp() {
		$op = $this->newChangeOpFactory()->newSetAliasesOp( 'en', array( 'foo' ) );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveAliasesOp() {
		$op = $this->newChangeOpFactory()->newRemoveAliasesOp( 'en', array( 'foo' ) );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetDescriptionOp() {
		$op = $this->newChangeOpFactory()->newSetDescriptionOp( 'en', 'foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveDescriptionOp() {
		$op = $this->newChangeOpFactory()->newRemoveDescriptionOp( 'en' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewSetLabelOp() {
		$op = $this->newChangeOpFactory()->newSetLabelOp( 'en', 'foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveLabelOp() {
		$op = $this->newChangeOpFactory()->newRemoveLabelOp( 'en' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
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
