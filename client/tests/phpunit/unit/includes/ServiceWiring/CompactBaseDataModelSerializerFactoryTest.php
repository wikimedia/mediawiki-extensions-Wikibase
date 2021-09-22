<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Serializers\SerializerFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CompactBaseDataModelSerializerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			SerializerFactory::class,
			$this->getService( 'WikibaseClient.CompactBaseDataModelSerializerFactory' )
		);
	}

}
