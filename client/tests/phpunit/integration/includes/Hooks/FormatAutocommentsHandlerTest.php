<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Language\Language;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\FormatAutocommentsHandler;

/**
 * @covers \Wikibase\Client\Hooks\FormatAutocommentsHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < mail@mariushoch.de >
 */
class FormatAutocommentsHandlerTest extends MediaWikiIntegrationTestCase {

	public function testOnFormatAutocomments() {
		$handler = new FormatAutocommentsHandler(
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' ),
			'somewiki'
		);

		$comment = '';
		$handler->onFormatAutocomments( $comment, false, 'something:Foo', true, null, true, 'somewiki' );
		$this->assertStringContainsString( '<span class="autocomment">(wikibase-entity-summary-something: Foo)', $comment );
	}

	public function testOnFormatAutocomments_linkNotToRepo() {
		$handler = new FormatAutocommentsHandler(
			$this->createMock( Language::class ),
			'somewiki'
		);

		$comment = '';
		$handler->onFormatAutocomments( $comment, false, 'something:Foo', true, null, true, 'anotherwiki' );
		$this->assertSame( '', $comment );
	}
}
