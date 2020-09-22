<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use MockHttpTrait;
use Wikibase\Repo\WikibaseRepo;

/**
 * This trait can be used to perform some standard actions on the fed props settings that may be
 * desired during tests.
 *
 * @author Addshore
 * @license GPL-2.0-or-later
 */
trait FederatedPropertiesTestTrait {
	use MockHttpTrait;

	protected function setSourceWikiUnavailable() {
		$this->installMockHttp( $this->makeFakeHttpRequest( '', 0 ) );
		$this->setWbSetting( 'federatedPropertiesSourceScriptUrl', '255.255.255.255/' );
	}

	protected function setFederatedPropertiesEnabled() {
		$this->setWbSetting( 'federatedPropertiesEnabled', true );
	}

	private function setWbSetting( string $name, $value ) {
		$this->setWbSettingInGlobalIfMwIntegrationTest( $name, $value );
		$this->setWbSettingInSettings( $name, $value );
	}

	private function setWbSettingInSettings( string $name, $value ) {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( $name, $value );
	}

	/**
	 * Only set the global value if this trait is being used in a MediaWiki integration test.
	 * These tests will automatically reset the global at the end of processing a test.
	 */
	private function setWbSettingInGlobalIfMwIntegrationTest( string $name, $value ) {
		if ( method_exists( $this, 'setMwGlobals' ) ) {
			global $wgWBRepoSettings;
			$newRepoSettings = $wgWBRepoSettings;
			$newRepoSettings[$name] = $value;
			$this->setMwGlobals( 'wgWBRepoSettings', $newRepoSettings );
		}
	}

	public function testFederatedPropertiesEnabled() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->assertSame( true, $settings->getSetting( 'federatedPropertiesEnabled' ) );
	}

}
