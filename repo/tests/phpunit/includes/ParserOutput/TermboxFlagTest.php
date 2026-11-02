<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWiki\Registration\ExtensionRegistry;
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
			new SettingsArray( [ TermboxFlag::TERMBOX_FLAG => false ] ),
			$this->createMock( ExtensionRegistry::class )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testGivenMobileExtensionNotLoaded_shouldRenderTermboxReturnsFalse() {
		$flag = new TermboxFlag(
			new SettingsArray( [ TermboxFlag::TERMBOX_FLAG => true ] ),
			$this->newExtensionRegistryWithMobileExtension( false )
		);
		$this->assertFalse( $flag->shouldRenderTermbox() );
	}

	public function testAllTrue_shouldRenderTermboxReturnsTrue() {
		$this->markTestSkippedIfExtensionNotLoaded( 'MobileFrontend' );
		$flag = new TermboxFlag(
			new SettingsArray( [ TermboxFlag::TERMBOX_FLAG => true ] ),
			$this->newExtensionRegistryWithMobileExtension( true )
		);
		$this->assertSame( MobileContext::singleton()->shouldDisplayMobileView(), $flag->shouldRenderTermbox() );
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
