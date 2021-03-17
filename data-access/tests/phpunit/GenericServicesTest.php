<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\GenericServices;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @covers \Wikibase\DataAccess\GenericServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GenericServicesTest extends \PHPUnit\Framework\TestCase {

	public function testGetCompactSerializerFactory() {
		$services = $this->newGenericServices();
		$this->assertInstanceOf( SerializerFactory::class, $services->getCompactBaseDataModelSerializerFactory() );
	}

	private function newGenericServices() {
		return new GenericServices( new EntityTypeDefinitions( [] ) );
	}

}
