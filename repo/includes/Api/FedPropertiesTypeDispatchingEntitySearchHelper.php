<?php

namespace Wikibase\Repo\Api;

/**
 * EntitySearchHelper implementation invoked when federated properties is enabled
 * which uses entity configuration to instantiate the specific handler.
 *
 * @license GPL-2.0-or-later
 */
class FedPropertiesTypeDispatchingEntitySearchHelper implements EntitySearchHelper {

	/**
	 * @var EntitySearchHelper
	 */
	private $federatedPropertiesEntitySearchHelper;

	/**
	 * @var TypeDispatchingEntitySearchHelper
	 */
	private $typeDispatchingEntitySearchHelper;

	public function __construct(
		EntitySearchHelper $federatedPropertiesEntitySearchHelper,
		TypeDispatchingEntitySearchHelper $typeDispatchingEntitySearchHelper
	) {
		$this->federatedPropertiesEntitySearchHelper = $federatedPropertiesEntitySearchHelper;
		$this->typeDispatchingEntitySearchHelper = $typeDispatchingEntitySearchHelper;
	}

	/**
	 * @inheritDoc
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage,
		?string $profileContext
	) {
		$helper = ( $entityType === 'property' ) ?
			$this->federatedPropertiesEntitySearchHelper :
			$this->typeDispatchingEntitySearchHelper;

		return $helper->getRankedSearchResults(
			$text,
			$languageCode,
			$entityType,
			$limit,
			$strictLanguage,
			$profileContext
		);
	}

}
