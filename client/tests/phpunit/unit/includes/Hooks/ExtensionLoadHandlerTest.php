<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\HookContainer\HookContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Client\Api\ApiFormatReference;
use Wikibase\Client\Hooks\ChangesListSpecialPageHookHandler;
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

	/**
	 * @param array &$hooks
	 *
	 * @return MockObject&HookContainer
	 */
	private function getFauxHookContainer( &$hooks ) {
		$container = $this->createMock( HookContainer::class );
		$container->method( 'register' )->willReturnCallback(
			function ( $name, $handler ) use ( &$hooks ) {
				$hooks[$name][] = $handler;
			}
		);

		return $container;
	}

	public function testGetHooks() {
		$actualHooks = [];
		$container = $this->getFauxHookContainer( $actualHooks );

		$handler = new ExtensionLoadHandler( $container );
		$handler->registerHooks();

		$expectedHooks = [
			'ChangesListSpecialPageStructuredFilters' => [
				ChangesListSpecialPageHookHandler::class . '::onChangesListSpecialPageStructuredFilters',
			],
		];
		$this->assertSame( $expectedHooks, $actualHooks );
	}

	public function testGetApiFormatReferenceSpec_settingTrue() {
		$handler = new ExtensionLoadHandler( $this->createMock( HookContainer::class ) );

		$spec = $handler->getApiFormatReferenceSpec( [ 'dataBridgeEnabled' => true ] );

		$this->assertNotNull( $spec );
		$this->assertSame( ApiFormatReference::class, $spec['class'] );
	}

	/** @dataProvider provideNotTrueDataBridgeEnabledSettings */
	public function testGetApiFormatReferenceSpec_settingNotTrue( array $settings ) {
		$handler = new ExtensionLoadHandler( $this->createMock( HookContainer::class ) );

		$spec = $handler->getApiFormatReferenceSpec( $settings );

		$this->assertNull( $spec );
	}

	public static function provideNotTrueDataBridgeEnabledSettings(): iterable {
		yield 'false' => [ [ 'dataBridgeEnabled' => false ] ];
		yield 'absent' => [ [] ];
	}

}
