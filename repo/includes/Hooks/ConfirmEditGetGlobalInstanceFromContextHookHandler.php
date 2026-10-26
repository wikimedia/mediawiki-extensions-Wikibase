<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Context\IContextSource;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use Wikibase\Lib\SettingsArray;

/**
 * Selects the edit CAPTCHA configuration for entity modification special pages.
 * @see \MediaWiki\Extension\ConfirmEdit\Hooks\ConfirmEditGetGlobalInstanceFromContextHook
 *
 * @license GPL-2.0-or-later
 */
class ConfirmEditGetGlobalInstanceFromContextHookHandler {

	private SettingsArray $repoSettings;

	public function __construct( SettingsArray $repoSettings ) {
		$this->repoSettings = $repoSettings;
	}

	public function onConfirmEditGetGlobalInstanceFromContext( IContextSource $context, string &$action ): void {
		if ( !$this->repoSettings->getSetting( 'tmpEnableHCaptchaIntegration' ) ) {
			return;
		}
		foreach ( [ 'SetLabel', 'SetDescription', 'SetAliases', 'SetLabelDescriptionAliases', 'SetSiteLink' ] as $name ) {
			if ( $context->getTitle()->isSpecial( $name ) ) {
				$action = CaptchaTriggers::EDIT;
				return;
			}
		}
	}

}
