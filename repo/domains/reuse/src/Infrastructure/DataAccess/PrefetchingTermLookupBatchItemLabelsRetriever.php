<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupBatchItemLabelsRetriever implements BatchItemLabelsRetriever {

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	/**
	 * @param ItemId[] $itemIds
	 * @param string[] $languageCodes
	 *
	 * @return ItemLabelsBatch
	 */
	public function getItemLabels( array $itemIds, array $languageCodes ): ItemLabelsBatch {

		$this->termLookup->prefetchTerms( $itemIds, [ TermTypes::TYPE_LABEL ], $languageCodes );
		$labelsByItemId = [];

		foreach ( $itemIds as $itemId ) {
			$labelsByItemId[ $itemId->getSerialization() ] =
				$this->buildLabelsFromArray( $this->termLookup->getLabels( $itemId, $languageCodes ) );
		}

		return new ItemLabelsBatch( $labelsByItemId );
	}

	private function buildLabelsFromArray( array $labels ): Labels {
		$result = new Labels();
		foreach ( $labels as $lang => $text ) {
			$result[ $lang ] = new Label( $lang, $text );
		}

		return $result;
	}

}
