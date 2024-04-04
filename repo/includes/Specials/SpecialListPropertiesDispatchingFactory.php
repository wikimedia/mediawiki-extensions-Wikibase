<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Specials;

use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;
use Wikibase\View\EntityIdFormatterFactory;

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
	 */
	public static function factory(
		DataTypeFactory $dataTypeFactory,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		EntityTitleLookup $entityTitleLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		PropertyInfoLookup $propertyInfoLookup,
		SettingsArray $repoSettings
	) {
		if ( $repoSettings->getSetting( 'federatedPropertiesEnabled' ) ) {
			return new SpecialListFederatedProperties(
				$repoSettings->getSetting( 'federatedPropertiesSourceScriptUrl' )
			);
		}

		return new SpecialListProperties(
			$dataTypeFactory,
			$propertyInfoLookup,
			$labelDescriptionLookupFactory,
			$entityIdFormatterFactory,
			$entityTitleLookup
		);
	}
}
