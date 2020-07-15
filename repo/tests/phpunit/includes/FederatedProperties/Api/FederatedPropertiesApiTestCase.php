<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Api;

use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 *
 * @author Tobias Andersson
 */
abstract class FederatedPropertiesApiTestCase extends WikibaseApiTestCase {

	protected function setSourceWikiUnavailable() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( 'federatedPropertiesSourceScriptUrl', '255.255.255.255/' );
	}

	public function testFederatedPropertiesEnabled() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->assertSame( true, $settings->getSetting( 'federatedPropertiesEnabled' ) );
	}

	protected function setUp(): void {
		parent::setUp();

		global $wgWBRepoSettings;
		$newRepoSettings = $wgWBRepoSettings;
		$newRepoSettings['federatedPropertiesEnabled'] = true;

		$this->setMwGlobals( 'wgWBRepoSettings', $newRepoSettings );
	}
}
