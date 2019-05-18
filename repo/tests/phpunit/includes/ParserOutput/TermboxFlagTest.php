<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxFlag
 *
 * @group Wikibase
 * @group NotIsolatedUnitTest
 *
 * @license GPL-2.0-or-later
 */
class TermboxFlagTest extends TestCase {

	use PHPUnit4And6Compat;

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
		if ( !class_exists( 'MobileContext' ) ) {
			$this->markTestSkipped();
		}
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
