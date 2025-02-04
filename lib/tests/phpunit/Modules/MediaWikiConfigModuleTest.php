<?php

namespace Wikibase\Lib\Tests\Modules;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\Request\FauxRequest;
use MediaWiki\ResourceLoader as RL;
use Wikibase\Lib\Modules\MediaWikiConfigModule;
use Wikibase\Lib\Modules\MediaWikiConfigValueProvider;

/**
 * @covers \Wikibase\Lib\Modules\MediaWikiConfigModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MediaWikiConfigModuleTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor_returnsResourceLoaderModule() {
		$this->assertInstanceOf( RL\Module::class, $this->newInstance() );
	}

	public function testGetScript_returnsJavaScript() {
		$context = new RL\Context( $this->createMock( RL\ResourceLoader::class ), new FauxRequest() );
		$script = $this->newInstance()->getScript( $context );
		$this->assertStringStartsWith( 'mw.config.set({', $script );
		$this->assertStringContainsString( 'dummyKey', $script );
		$this->assertStringContainsString( 'dummyValue', $script );
	}

	public function testEnableModuleContentVersion_returnsTrue() {
		$this->assertTrue( $this->newInstance()->enableModuleContentVersion() );
	}

	private function newInstance() {
		return new MediaWikiConfigModule( [ 'getconfigvalueprovider' => function () {
			$provider = $this->createMock( MediaWikiConfigValueProvider::class );

			$provider->method( 'getKey' )
				->willReturn( 'dummyKey' );

			$provider->method( 'getValue' )
				->willReturn( 'dummyValue' );

			return $provider;
		} ] );
	}
}
