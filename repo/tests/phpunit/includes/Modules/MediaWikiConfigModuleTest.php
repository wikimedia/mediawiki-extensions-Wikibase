<?php

namespace Wikibase\Repo\Tests\Modules;

use PHPUnit4And6Compat;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Wikibase\Repo\Modules\MediaWikiConfigModule;
use Wikibase\Repo\Modules\MediaWikiConfigValueProvider;

/**
 * @covers Wikibase\Repo\Modules\MediaWikiConfigModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MediaWikiConfigModuleTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testConstructor_returnsResourceLoaderModule() {
		$this->assertInstanceOf( ResourceLoaderModule::class, $this->newInstance() );
	}

	public function testGetScript_returnsJavaScript() {
		$context = $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->never() )
			->method( $this->anything() );

		$script = $this->newInstance()->getScript( $context );
		$this->assertStringStartsWith( 'mw.config.set({', $script );
		$this->assertContains( 'dummyKey', $script );
		$this->assertContains( 'dummyValue', $script );
	}

	public function testEnableModuleContentVersion_returnsTrue() {
		$this->assertTrue( $this->newInstance()->enableModuleContentVersion() );
	}

	private function newInstance() {
		return new MediaWikiConfigModule( [ 'getconfigvalueprovider' => function () {
			$provider = $this->getMock( MediaWikiConfigValueProvider::class );

			$provider->expects( $this->any() )
				->method( 'getKey' )
				->will( $this->returnValue( 'dummyKey' ) );

			$provider->expects( $this->any() )
				->method( 'getValue' )
				->will( $this->returnValue( 'dummyValue' ) );

			return $provider;
		} ] );
	}

}
