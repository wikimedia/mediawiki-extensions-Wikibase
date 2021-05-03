<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\MediaWikiMessageInLanguageProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MessageInLanguageProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			MediaWikiMessageInLanguageProvider::class,
			$this->getService( 'WikibaseRepo.MessageInLanguageProvider' )
		);
	}

}
