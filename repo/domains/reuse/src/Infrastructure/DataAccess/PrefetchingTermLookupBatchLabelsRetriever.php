<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemLabelsRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchPropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupBatchLabelsRetriever implements BatchItemLabelsRetriever, BatchPropertyLabelsRetriever {

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	/**
	 * @inheritDoc
	 */
	public function getItemLabels( array $itemIds, array $languageCodes ): ItemLabelsBatch {
		return new ItemLabelsBatch( $this->getLabelsForEntities( $itemIds, $languageCodes ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getPropertyLabels( array $propertyIds, array $languageCodes ): PropertyLabelsBatch {
		return new PropertyLabelsBatch( $this->getLabelsForEntities( $propertyIds, $languageCodes ) );
	}

	private function getLabelsForEntities( array $entityIds, array $languageCodes ): array {
		$this->termLookup->prefetchTerms( $entityIds, [ TermTypes::TYPE_LABEL ], $languageCodes );

		$labelsByEntityId = [];
		foreach ( $entityIds as $entityId ) {
			$entityLabels = [];
			foreach ( $languageCodes as $language ) {
				$text = $this->termLookup->getPrefetchedTerm( $entityId, TermTypes::TYPE_LABEL, $language );
				if ( !is_string( $text ) ) {
					continue;
				}
				$entityLabels[] = new Label( $language, $text );
			}

			$labelsByEntityId[$entityId->getSerialization()] = new Labels( ...$entityLabels );
		}
		return $labelsByEntityId;
	}

}
