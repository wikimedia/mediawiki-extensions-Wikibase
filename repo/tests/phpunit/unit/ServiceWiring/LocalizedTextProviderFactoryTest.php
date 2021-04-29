<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\LocalizedTextProviderFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalizedTextProviderFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getLanguageFactory' );

		$this->assertInstanceOf(
			LocalizedTextProviderFactory::class,
			$this->getService( 'WikibaseRepo.LocalizedTextProviderFactory' )
		);
	}

}
