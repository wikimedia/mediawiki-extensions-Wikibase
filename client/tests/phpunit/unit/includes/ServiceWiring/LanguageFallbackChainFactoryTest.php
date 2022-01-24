<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\StaticContentLanguages;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageFallbackChainFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.TermsLanguages',
			new StaticContentLanguages( [] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageConverterFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFallback' );

		$this->assertInstanceOf(
			LanguageFallbackChainFactory::class,
			$this->getService( 'WikibaseClient.LanguageFallbackChainFactory' )
		);
	}

}
