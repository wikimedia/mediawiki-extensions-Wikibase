<?php

namespace Wikibase\Lib\Tests\DataValue;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;

/**
 * @covers \Wikibase\Lib\DataValue\UnmappedEntityIdValue
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnmappedEntityIdValueTest extends TestCase {

	public function testGetValue() {
		$value = new UnmappedEntityIdValue( 'FOOBAR13' );
		$this->assertSame( 'FOOBAR13', $value->getValue() );
	}

	public function testGetType() {
		$this->assertSame( 'wikibase-unmapped-entityid', UnmappedEntityIdValue::getType() );
	}

}
