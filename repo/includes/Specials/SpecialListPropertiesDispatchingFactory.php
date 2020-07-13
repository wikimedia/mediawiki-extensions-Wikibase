<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Specials;

use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;
use Wikibase\Repo\WikibaseRepo;

/**
 * Factory to create Special:ListProperties based on whether federated properties
 * setting is enabled ot not. If it's enabled, it returns an instance of SpecialListFederatedProperties
 * otherwise, it returns an instance of SpecialListProperties.
 * Both are built using global state.
 *
 * @license GPL-2.0-or-later
 */
class SpecialListPropertiesDispatchingFactory {

	/**
	 * @return SpecialListFederatedProperties|SpecialListProperties
	 * @throws \MWException
	 */
	public static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		if ( $wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
			return new SpecialListFederatedProperties(
				$wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' )
			);
		}

		$prefetchingTermLookup = $wikibaseRepo->getPrefetchingTermLookup();
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$prefetchingTermLookup,
			$wikibaseRepo->getLanguageFallbackChainFactory()
				->newFromLanguage( $wikibaseRepo->getUserLanguage() )
		);
		$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( $wikibaseRepo->getUserLanguage() );
		return new SpecialListProperties(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoLookup(),
			$labelDescriptionLookup,
			$entityIdFormatter,
			$wikibaseRepo->getEntityTitleLookup(),
			$prefetchingTermLookup,
			$wikibaseRepo->getLanguageFallbackChainFactory()
		);
	}
}
