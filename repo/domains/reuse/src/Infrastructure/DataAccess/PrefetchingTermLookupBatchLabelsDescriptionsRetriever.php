<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Description;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Label;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemDescriptionsRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemLabelsRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchPropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupBatchLabelsDescriptionsRetriever
	implements BatchItemLabelsRetriever, BatchPropertyLabelsRetriever, BatchItemDescriptionsRetriever {

	public function __construct( private PrefetchingTermLookup $termLookup ) {
	}

	/**
	 * @inheritDoc
	 */
	public function getItemLabels( array $itemIds, array $languageCodes ): ItemLabelsBatch {
		return new ItemLabelsBatch( $this->getTermsForEntities( TermTypes::TYPE_LABEL, $itemIds, $languageCodes ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getPropertyLabels( array $propertyIds, array $languageCodes ): PropertyLabelsBatch {
		return new PropertyLabelsBatch( $this->getTermsForEntities( TermTypes::TYPE_LABEL, $propertyIds, $languageCodes ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getItemDescriptions( array $itemIds, array $languageCodes ): ItemDescriptionsBatch {
		return new ItemDescriptionsBatch( $this->getTermsForEntities( TermTypes::TYPE_DESCRIPTION, $itemIds, $languageCodes ) );
	}

	private function getTermsForEntities( string $termType, array $entityIds, array $languageCodes ): array {
		$singleTermClassMap = [ TermTypes::TYPE_LABEL => Label::class, TermTypes::TYPE_DESCRIPTION => Description::class ];
		$multipleTermsClassMap = [ TermTypes::TYPE_LABEL => Labels::class, TermTypes::TYPE_DESCRIPTION => Descriptions::class ];
		$this->termLookup->prefetchTerms( $entityIds, [ $termType ], $languageCodes );

		$termsByEntityId = [];
		foreach ( $entityIds as $entityId ) {
			$terms = [];
			foreach ( $languageCodes as $language ) {
				$text = $this->termLookup->getPrefetchedTerm( $entityId, $termType, $language );
				if ( !is_string( $text ) ) {
					continue;
				}
				$terms[] = new $singleTermClassMap[$termType]( $language, $text );
			}

			$termsByEntityId[$entityId->getSerialization()] = new $multipleTermsClassMap[$termType]( ...$terms );
		}

		return $termsByEntityId;
	}
}
