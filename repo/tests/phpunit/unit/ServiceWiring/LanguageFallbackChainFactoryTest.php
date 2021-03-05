<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageFallbackChainFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageConverterFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFallback' );

		$this->assertInstanceOf(
			LanguageFallbackChainFactory::class,
			$this->getService( 'WikibaseRepo.LanguageFallbackChainFactory' )
		);
	}

}
