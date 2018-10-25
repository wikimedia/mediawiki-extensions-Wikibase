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
			$this->newExtensionRegistryWithMobileExtension( true ),
			$this->newMobileContext( true )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testGivenMobileExtensionNotLoaded_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( false ),
			$this->newMobileContext( true )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testGivenMobileContextFalse_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( true ),
			$this->newMobileContext( false )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testAllTrue_shouldRenderTermboxReturnsTrue() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( true ),
			$this->newMobileContext( true )
		);
		$this->assertTrue( $flag->shouldRenderTermbox() );
	}

	private function newSettingsWithFeatureFlag( $setting ) {
		$settings = $this->createMock( SettingsArray::class );
		$settings->method( 'getSetting' )
			->with( TermboxFlag::TERMBOX_FLAG )
			->willReturn( $setting );

		return $settings;
	}

	private function newExtensionRegistryWithMobileExtension( $isEnabled ) {
		$registry = $this->createMock( ExtensionRegistry::class );
		$registry->method( 'isLoaded' )
			->with( 'MobileFrontend' )
			->willReturn( $isEnabled );

		return $registry;
	}

	private function newMobileContext( $isMobileRequest ) {
		$context = $this->createMock( MobileContext::class );
		$context->method( 'shouldDisplayMobileView' )
			->willReturn( $isMobileRequest );

		return $context;
	}

}
