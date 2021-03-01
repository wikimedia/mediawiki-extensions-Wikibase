<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataValueFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.DataValueDeserializer',
			new DataValueDeserializer() );

		$this->assertInstanceOf(
			DataValueFactory::class,
			$this->getService( 'WikibaseRepo.DataValueFactory' )
		);
	}

}
