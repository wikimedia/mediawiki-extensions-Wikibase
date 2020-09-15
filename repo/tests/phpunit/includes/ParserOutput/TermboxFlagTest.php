<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ExtensionRegistry;
use MobileContext;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ParserOutput\TermboxFlag;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxFlag
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxFlagTest extends TestCase {

	public function testGetInstance() {
		$this->assertInstanceOf( TermboxFlag::class, TermboxFlag::getInstance() );
	}

	public function testGivenDesktopFeatureFlagSetFalse_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( false, true ),
			$this->createMock( ExtensionRegistry::class )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testGivenDesktopFeatureFlagSetTrue_shouldRenderTermboxReturnsTrue() {
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true, true ),
			$this->createMock( ExtensionRegistry::class )
		);
		$this->assertTrue( $flag->shouldRenderTermbox() );
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
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$this->markTestSkipped();
		}
		$flag = new TermboxFlag(
			$this->newSettingsWithFeatureFlag( true ),
			$this->newExtensionRegistryWithMobileExtension( true )
		);
		$this->assertSame( MobileContext::singleton()->shouldDisplayMobileView(), $flag->shouldRenderTermbox() );
	}

	private function newSettingsWithFeatureFlag( $setting, $desktop = false ) {
		$settings = $this->createMock( SettingsArray::class );
		$settings->expects( $this->atMost( 2 ) )
			->method( 'getSetting' )
			->with( $this->logicalOr(
				$this->equalTo( TermboxFlag::TERMBOX_DESKTOP_FLAG ),
				$this->equalTo( TermboxFlag::TERMBOX_FLAG )
			) )
			->willReturnCallback( function ( $key ) use ( $setting, $desktop ) {
				if (
					( $key === TermboxFlag::TERMBOX_DESKTOP_FLAG && $desktop ) ||
					( $key === TermboxFlag::TERMBOX_FLAG && !$desktop )
				) {
					return $setting;
				}
				return false;
			} );

		return $settings;
	}

	private function newExtensionRegistryWithMobileExtension( $isEnabled ) {
		$registry = $this->createMock( ExtensionRegistry::class );
		$registry->expects( $this->atMost( 2 ) )
			->method( 'isLoaded' )
			->with( 'MobileFrontend' )
			->willReturn( $isEnabled );

		return $registry;
	}

}
