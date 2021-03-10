<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValueFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.DataTypeDefinitions',
			new DataTypeDefinitions( [] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getContentLanguage' );
		$this->mockService( 'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );

		$this->assertInstanceOf(
			OutputFormatValueFormatterFactory::class,
			$this->getService( 'WikibaseClient.ValueFormatterFactory' )
		);
	}

}
