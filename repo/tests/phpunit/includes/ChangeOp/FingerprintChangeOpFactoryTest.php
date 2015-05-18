<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\FingerprintChangeOpFactory;

/**
 * @covers Wikibase\ChangeOp\FingerprintChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FingerprintChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return FingerprintChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		return new FingerprintChangeOpFactory(
			$mockProvider->getMockTermValidatorFactory()
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

}
