<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\GenericServices;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers \Wikibase\DataAccess\GenericServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GenericServicesTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor() {
		$this->assertInstanceOf( GenericServices::class, $this->newGenericServices() );
	}

	private function newGenericServices() {
		return new GenericServices( new EntityTypeDefinitions( [] ) );
	}

}
