<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;

/**
 * A {@link FallbackLabelDescriptionLookup} that dispatches between two other lookups,
 * using one for federated property IDs and one for everything else.
 *
 * This is necessary because the lookup implementation we want to use for most entity IDs,
 * {@link CachingFallbackLabelDescriptionLookup}, does not support federated properties,
 * because it requires an EntityRevisionLookup (to get the latest revision ID for the cache key),
 * which is not available for federated properties as of July 2022.
 *
 * This class should only be used by {@link FallbackLabelDescriptionLookupFactory}.
 * Do not use it directly. Once the standard lookup supports federated properties,
 * this class can hopefully be removed again.
 *
 * @license GPL-2.0-or-later
 */
class DispatchingFallbackLabelDescriptionLookup implements FallbackLabelDescriptionLookup {

	/** @var FallbackLabelDescriptionLookup */
	private $standardLookup;
	/** @var FallbackLabelDescriptionLookup */
	private $federatedPropertiesLookup;

	/**
	 * @param FallbackLabelDescriptionLookup $standardLookup
	 * The lookup used for most entity IDs.
	 * Usually a {@link CachingFallbackLabelDescriptionLookup}.
	 * @param FallbackLabelDescriptionLookup $federatedPropertiesLookup
	 * The lookup used for federated property IDs.
	 * Usually a {@link LanguageFallbackLabelDescriptionLookup}.
	 */
	public function __construct(
		FallbackLabelDescriptionLookup $standardLookup,
		FallbackLabelDescriptionLookup $federatedPropertiesLookup
	) {
		$this->standardLookup = $standardLookup;
		$this->federatedPropertiesLookup = $federatedPropertiesLookup;
	}

	public function getLabel( EntityId $entityId ) {
		return $this->getLookup( $entityId )->getLabel( $entityId );
	}

	public function getDescription( EntityId $entityId ) {
		return $this->getLookup( $entityId )->getDescription( $entityId );
	}

	private function getLookup( EntityId $entityId ): FallbackLabelDescriptionLookup {
		return $entityId instanceof FederatedPropertyId ?
			$this->federatedPropertiesLookup :
			$this->standardLookup;
	}

}
