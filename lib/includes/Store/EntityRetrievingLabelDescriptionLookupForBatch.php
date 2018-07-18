<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;

class EntityRetrievingLabelDescriptionLookupForBatch implements LabelDescriptionLookupForBatch {

	/**
	 * @var EntityRetrievingTermLookup
	 */
	private $termLookup;

	public function getLabels(array $ids, array $languageCodes) {
		$labels = [];

		foreach ( $ids as $id ) {
			$labels[$id->getSerialization()] = $this->termLookup->getLabels( $id, $languageCodes );
		}

		return $labels;
	}

	public function getDescriptions(array $ids, array $languageCodes) {
		// TODO: Implement getDescriptions() method.
	}
}