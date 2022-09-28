<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ParserOutput\TermboxFlag;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxFlag
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxFlagTest extends \MediaWikiIntegrationTestCase {

	public function testGetInstance() {
		$this->assertInstanceOf( TermboxFlag::class, TermboxFlag::getInstance() );
	}

	public function testGivenFeatureFlagSetFalse_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( false ),
			$this->createMock( ExtensionRegistry::class )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testGivenMobileExtensionNotLoaded_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( false )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testAllTrue_shouldRenderTermboxReturnsTrue() {
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( true )
		);
		$this->assertSame( MobileContext::singleton()->shouldDisplayMobileView(), $flag->shouldRenderTermbox() );
	}

	private function newSettingsWithFeatureFlag( $setting ) {
		$settings = $this->createMock( SettingsArray::class );
		$settings->expects( $this->once() )
			->method( 'getSetting' )
			->with( TermboxFlag::TERMBOX_FLAG )
			->willReturn( $setting );

		return $settings;
	}

	private function newExtensionRegistryWithMobileExtension( $isEnabled ) {
		$registry = $this->createMock( ExtensionRegistry::class );
		$registry->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'MobileFrontend' )
			->willReturn( $isEnabled );

		return $registry;
	}

}
