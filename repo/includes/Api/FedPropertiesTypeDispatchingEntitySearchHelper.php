<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Interactors\TermSearchResult;

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
	 * Get entities matching the search term.
	 *
	 * @param string $text
	 * @param string $languageCode
	 * @param string $entityType
	 * @param int $limit
	 * @param bool $strictLanguage
	 *
	 * @return TermSearchResult[] Key: string Serialized EntityId
	 */
	public function getRankedSearchResults(
		$text,
		$languageCode,
		$entityType,
		$limit,
		$strictLanguage
	) {
		$helper = ( $entityType === 'property' ) ?
			$this->federatedPropertiesEntitySearchHelper :
			$this->typeDispatchingEntitySearchHelper;

		return $helper->getRankedSearchResults(
			$text,
			$languageCode,
			$entityType,
			$limit,
			$strictLanguage
		);
	}

}
