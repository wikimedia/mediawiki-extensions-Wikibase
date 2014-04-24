<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\SiteLinkChangeOpFactory;

/**
 * @covers Wikibase\ChangeOp\SiteLinkChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkChangeOpFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SiteLinkChangeOpFactory
	 */
	protected function newChangeOpFactory() {
		return new SiteLinkChangeOpFactory();
	}

	public function testNewSetSiteLinkOp() {
		$op = $this->newChangeOpFactory()->newSetSiteLinkOp( 'enwiki', 'Foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

	public function testNewRemoveSiteLinkOp() {
		$op = $this->newChangeOpFactory()->newRemoveSiteLinkOp( 'enwiki', 'Foo' );
		$this->assertInstanceOf( 'Wikibase\ChangeOp\ChangeOp', $op );
	}

}
