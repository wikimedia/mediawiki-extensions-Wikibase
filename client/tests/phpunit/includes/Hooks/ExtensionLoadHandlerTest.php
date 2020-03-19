<?php

namespace Wikibase\Client\Tests\Hooks;

use ExtensionRegistry;
use PHPUnit\Framework\TestCase;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandlers;
use Wikibase\Client\Hooks\EchoNotificationsHandlers;
use Wikibase\Client\Hooks\ExtensionLoadHandler;

/**
 * @covers \Wikibase\Client\Hooks\ExtensionLoadHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExtensionLoadHandlerTest extends TestCase {

	public function testGetHooks_withEcho() {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->method( 'isLoaded' )
			->with( 'Echo' )
			->willReturn( true );
		$handler = new ExtensionLoadHandler( $extensionRegistry );

		$actualHooks = $handler->getHooks();

		$expectedHooks = [
			'LocalUserCreated' => [
				EchoNotificationsHandlers::class . '::onLocalUserCreated',
			],
			'WikibaseHandleChange' => [
				EchoNotificationsHandlers::class . '::onWikibaseHandleChange',
			],
			'ChangesListSpecialPageStructuredFilters' => [
				ChangesListSpecialPageHookHandlers::class . '::onChangesListSpecialPageStructuredFilters',
			],
		];
		$this->assertSame( $expectedHooks, $actualHooks );
	}

	public function testGetHooks_withoutEcho() {
		$extensionRegistry = $this->createMock( ExtensionRegistry::class );
		$extensionRegistry->method( 'isLoaded' )
			->with( 'Echo' )
			->willReturn( false );
		$handler = new ExtensionLoadHandler( $extensionRegistry );

		$actualHooks = $handler->getHooks();

		$expectedHooks = [
			'ChangesListSpecialPageStructuredFilters' => [
				ChangesListSpecialPageHookHandlers::class . '::onChangesListSpecialPageStructuredFilters',
			],
		];
		$this->assertSame( $expectedHooks, $actualHooks );
	}

}
