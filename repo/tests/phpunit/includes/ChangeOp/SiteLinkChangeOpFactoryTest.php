<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;

/**
 * @covers Wikibase\ChangeOp\SiteLinkChangeOpFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
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
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

	public function testNewRemoveSiteLinkOp() {
		$op = $this->newChangeOpFactory()->newRemoveSiteLinkOp( 'enwiki' );
		$this->assertInstanceOf( ChangeOp::class, $op );
	}

}
