<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LanguageDirectionalityLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageNameUtils' );
		$this->assertInstanceOf(
			MediaWikiLanguageDirectionalityLookup::class,
			$this->getService( 'WikibaseRepo.LanguageDirectionalityLookup' )
		);
	}

}
