<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ReferenceFormatterFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReferenceFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Logger',
			new NullLogger() );
		$this->mockService( 'WikibaseClient.DataAccessSnakFormatterFactory',
			$this->createMock( DataAccessSnakFormatterFactory::class ) );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'wellKnownReferencePropertyIds' => [],
			] ) );

		$this->assertInstanceOf(
			ReferenceFormatterFactory::class,
			$this->getService( 'WikibaseClient.ReferenceFormatterFactory' )
		);
	}

}
