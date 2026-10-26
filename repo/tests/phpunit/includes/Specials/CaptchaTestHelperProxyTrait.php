<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Extension\ConfirmEdit\Tests\Integration\CaptchaTestHelperTrait;
use MediaWiki\Registration\ExtensionRegistry;

/**
 * Proxies ConfirmEdit's CaptchaTestHelperTrait so tests can run without ConfirmEdit loaded.
 *
 * @license GPL-2.0-or-later
 */
trait CaptchaTestHelperProxyTrait {

	/**
	 * Install hCaptcha config and clear ConfirmEdit's instance cache; skips the test if
	 * ConfirmEdit is absent.
	 *
	 * @param array $triggers Trigger flags merged over all-false defaults, e.g. [ 'edit' => true ].
	 * @param array $overrides Extra config overrides (e.g. CaptchaClass, CaptchaTriggersOnNamespace).
	 */
	private function configureHCaptcha( array $triggers = [], array $overrides = [] ): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' ) ) {
			$this->markTestSkipped( 'ConfirmEdit is not loaded' );
		}
		$this->overrideConfigValues( $overrides + [
			'CaptchaClass' => 'HCaptcha',
			'CaptchaTriggers' => $triggers + [
				'create' => false,
				'edit' => false,
				'addurl' => false,
				'createaccount' => false,
				'badlogin' => false,
				'badloginperuser' => false,
			],
			'CaptchaTriggersOnNamespace' => [],
			'HCaptchaSiteKey' => 'test-site-key',
			'HCaptchaSecretKey' => 'test-secret-key',
			'HCaptchaEnterprise' => false,
			'HCaptchaSecureEnclave' => false,
			'HCaptchaInvisibleMode' => false,
			'HCaptchaApiUrl' => 'https://js.hcaptcha.com/1/api.js',
			'HCaptchaApiUrlIntegrityHash' => '',
		] );
		$this->clearCaptchaFactoryGlobalInstances();
	}

	private function clearCaptchaFactoryGlobalInstances(): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' ) ) {
			return;
		}
		( new class {
			use CaptchaTestHelperTrait;

			public function clear(): void {
				self::clearCaptchaFactoryGlobalInstances();
			}
		} )->clear();
	}
}
