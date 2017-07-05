<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;

/**
 * @covers Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0+
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
		$op = $this->newChangeOpFactory()->newAddAliasesOp( 'en', [ 'foo' ] );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetAliasesOp() {
		$op = $this->newChangeOpFactory()->newSetAliasesOp( 'en', [ 'foo' ] );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveAliasesOp() {
		$op = $this->newChangeOpFactory()->newRemoveAliasesOp( 'en', [ 'foo' ] );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetDescriptionOp() {
		$op = $this->newChangeOpFactory()->newSetDescriptionOp( 'en', 'foo' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveDescriptionOp() {
		$op = $this->newChangeOpFactory()->newRemoveDescriptionOp( 'en' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewSetLabelOp() {
		$op = $this->newChangeOpFactory()->newSetLabelOp( 'en', 'foo' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveLabelOp() {
		$op = $this->newChangeOpFactory()->newRemoveLabelOp( 'en' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

}
