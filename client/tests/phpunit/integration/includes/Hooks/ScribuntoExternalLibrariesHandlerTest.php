<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWikiIntegrationTestCase;
use Wikibase\Client\DataAccess\Scribunto\WikibaseEntityLibrary;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary;
use Wikibase\Client\Hooks\ScribuntoExternalLibrariesHandler;

/**
 * @covers \Wikibase\Client\Hooks\ScribuntoExternalLibrariesHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ScribuntoExternalLibrariesHandlerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Scribunto' );
	}

	public function testOnScribuntoExternalLibraries() {
		$extraLibraries = [ 'foo' => 'bar' ];

		$handler = new ScribuntoExternalLibrariesHandler( true );
		$handler->onScribuntoExternalLibraries( 'lua', $extraLibraries );

		$this->assertCount( 3, $extraLibraries );
		$this->assertSame( 'bar', $extraLibraries['foo'] );
		$this->assertSame( WikibaseLibrary::class, $extraLibraries['mw.wikibase'] );
		$this->assertSame( WikibaseEntityLibrary::class, $extraLibraries['mw.wikibase.entity'] );
	}

	public function testOnScribuntoExternalLibraries_unchanged() {
		$extraLibraries = [ 'foo' => 'bar' ];

		$handler = new ScribuntoExternalLibrariesHandler( false );
		$handler->onScribuntoExternalLibraries( 'lua', $extraLibraries );

		$handler = new ScribuntoExternalLibrariesHandler( true );
		$handler->onScribuntoExternalLibraries( 'python', $extraLibraries );

		$this->assertCount( 1, $extraLibraries );
		$this->assertSame( 'bar', $extraLibraries['foo'] );
	}

}
