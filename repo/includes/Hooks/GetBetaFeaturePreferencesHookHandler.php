<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use MediaWiki\User\User;
use Wikibase\Lib\SettingsArray;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * Hook handler that registers beta features with the BetaFeatures extension.
 * @see https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:BetaFeatures/Hooks/GetBetaFeaturePreferences
 * @see \MediaWiki\Extension\BetaFeatures\Hooks\GetBetaFeaturePreferencesHook
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GetBetaFeaturePreferencesHookHandler {

	public function __construct(
		private readonly string $extensionAssetsPath,
		private readonly bool $enableMobileEditingUIBetaFeature,
	) {
	}

	public static function factory( Config $mainConfig, SettingsArray $settings ): self {
		return new self(
			$mainConfig->get( MainConfigNames::ExtensionAssetsPath ),
			$settings->getSetting( 'tmpEnableMobileEditingUIBetaFeature' ),
		);
	}

	public function onGetBetaFeaturePreferences( User $user, array &$betaFeatures ): void {
		if ( !$this->enableMobileEditingUIBetaFeature ) {
			return;
		}
		$betaFeatures[Wbui2025FeatureFlag::OPTION_NAME] = [
			'label-message' => 'wikibase-mobile-editing-ui-beta-feature-message',
			'desc-message' => 'wikibase-mobile-editing-ui-beta-feature-description',
			'info-link' =>
				'https://www.wikidata.org/wiki/Wikidata:Usability_and_usefulness/Item_editing_experience/Mobile_editing_of_statements',
			'discussion-link' =>
				'https://www.wikidata.org/wiki/Wikidata_talk:Usability_and_usefulness/Item_editing_experience/Mobile_editing_of_statements',
			'screenshot' =>
				"{$this->extensionAssetsPath}/Wikibase/repo/resources/wikibase.wbui2025/images/mobile.svg",
		];
	}

}
