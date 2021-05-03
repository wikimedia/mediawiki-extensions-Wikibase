<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\MediaWikiMessageInLanguageProvider;

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
			$this->getService( 'WikibaseClient.MessageInLanguageProvider' )
		);
	}

}
