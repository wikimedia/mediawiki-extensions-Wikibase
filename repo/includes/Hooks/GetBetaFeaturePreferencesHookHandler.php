<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\User\User;
use Wikibase\Lib\SettingsArray;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * Hook handler that registers beta features with the BetaFeatures extension.
 * @see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Hooks/GetBetaFeatureDependencyHooks
 * @see \MediaWiki\Extension\BetaFeatures\Hooks\GetBetaFeaturePreferencesHook
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GetBetaFeaturePreferencesHookHandler {

	private bool $enableMobileEditingUIBetaFeature;

	public function __construct( bool $enableMobileEditingUIBetaFeature ) {
		$this->enableMobileEditingUIBetaFeature = $enableMobileEditingUIBetaFeature;
	}

	public static function factory( SettingsArray $settings ): self {
		return new self( $settings->getSetting( 'tmpEnableMobileEditingUIBetaFeature' ) );
	}

	public function onGetBetaFeaturePreferences( User $user, array &$betaFeatures ): void {
		if ( !$this->enableMobileEditingUIBetaFeature ) {
			return;
		}
		$betaFeatures[Wbui2025FeatureFlag::OPTION_NAME] = [
			'label-message' => 'wikibase-mobile-editing-ui-beta-feature-message',
			'desc-message' => 'wikibase-mobile-editing-ui-beta-feature-description',
			// These links are required, but given we don't have community pages set up yet, link to Phab.
			'info-link' => 'https://phabricator.wikimedia.org/T394621',
			'discussion-link' => 'https://phabricator.wikimedia.org/T394621',
		];
	}

}
