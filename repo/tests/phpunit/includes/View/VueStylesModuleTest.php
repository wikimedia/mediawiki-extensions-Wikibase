<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\View;

use MediaWiki\ResourceLoader\Context;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\View\VueStylesModule;
use Wikibase\View\Wbui2025ComponentsFactory;
use WMDE\VueJsTemplating\App;

/**
 * @covers \Wikibase\Repo\View\VueStylesModule
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0-or-later
 * @author Mahmoud Abdelsattar <mahmoud.abdelsattar@wikimedia.de>
 */
class VueStylesModuleTest extends TestCase {

	/**
	 * @param array<string, string> $components Map of component name to Vue SFC content
	 */
	private function makeModule( array $components ): VueStylesModule {
		$relPaths = array_map( fn( $name ) => "resources/$name.vue", array_keys( $components ) );

		$factory = $this->createMock( Wbui2025ComponentsFactory::class );
		$factory->method( 'getTemplateFiles' )->willReturn( array_combine( array_keys( $components ), $relPaths ) );
		$factory->method( 'registerComponentTemplates' )->willReturnCallback(
			function ( App $app ) use ( $components ): void {
				foreach ( $components as $name => $content ) {
					$app->registerComponentTemplate( $name, fn() => $content );
				}
			}
		);

		$module = $this->getMockBuilder( VueStylesModule::class )
			->onlyMethods( [ 'getComponentsFactory', 'saveFileDependencies' ] )
			->getMock();
		$module->method( 'getComponentsFactory' )->willReturn( $factory );
		$module->method( 'saveFileDependencies' )->willReturn( null );

		return $module;
	}

	private function makeContext(): Context {
		$context = $this->createMock( Context::class );
		$context->method( 'getDirection' )->willReturn( 'ltr' );
		return $context;
	}

	public function testGetStyles_returnsNoStylesWhenNoStyleBlockPresent(): void {
		$styles = $this->makeModule( [
			'my-component' => '<template><div></div></template>',
		] )->getStyles( $this->makeContext() );

		$this->assertArrayNotHasKey( 'all', $styles );
	}

	public function testGetStyles_combinesStylesFromMultipleVueFiles(): void {
		$styles = $this->makeModule( [
			'component-a' => '<template><div></div></template><style>.component-a { color: red; }</style>',
			'component-b' => '<template><div></div></template><style>.component-b { color: blue; }</style>',
		] )->getStyles( $this->makeContext() );

		$this->assertStringContainsString( '.component-a', $styles['all'] );
		$this->assertStringContainsString( '.component-b', $styles['all'] );
	}

}
