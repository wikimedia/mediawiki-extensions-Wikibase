<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Specials;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;
use Wikibase\Repo\Store\Store;
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
	public static function factory(
		DataTypeFactory $dataTypeFactory,
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		PrefetchingTermLookup $prefetchingTermLookup,
		SettingsArray $repoSettings,
		Store $store
	) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		if ( $repoSettings->getSetting( 'federatedPropertiesEnabled' ) ) {
			return new SpecialListFederatedProperties(
				$repoSettings->getSetting( 'federatedPropertiesSourceScriptUrl' )
			);
		}

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$prefetchingTermLookup,
			$languageFallbackChainFactory->newFromLanguage( WikibaseRepo::getUserLanguage() )
		);
		$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( WikibaseRepo::getUserLanguage() );
		return new SpecialListProperties(
			$dataTypeFactory,
			// TODO move PropertyInfoLookup to service container and inject it directly
			$store->getPropertyInfoLookup(),
			$labelDescriptionLookup,
			$entityIdFormatter,
			$entityTitleLookup,
			$prefetchingTermLookup,
			$languageFallbackChainFactory
		);
	}
}
