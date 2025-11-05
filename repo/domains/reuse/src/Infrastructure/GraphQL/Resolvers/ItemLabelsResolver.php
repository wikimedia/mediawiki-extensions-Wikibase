<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels\BatchGetItemLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelsResolver {
	private array $itemsToFetch = [];
	private array $languagesToFetch = [];
	private ?ItemLabelsBatch $labelsBatch = null;

	public function __construct(
		private readonly BatchGetItemLabels $batchGetItemLabels
	) {
	}

	public function resolve( ItemId $itemId, string $languageCode ): Deferred {
		$this->itemsToFetch[] = $itemId->getSerialization();
		$this->languagesToFetch[] = $languageCode;

		return new Deferred( function() use ( $itemId, $languageCode ) {
			if ( !$this->labelsBatch ) {
				$this->labelsBatch = $this->batchGetItemLabels
					->execute( new BatchGetItemLabelsRequest(
						array_values( array_unique( $this->itemsToFetch ) ),
						array_values( array_unique( $this->languagesToFetch ) ),
					) )
					->batch;
			}

			return $this->labelsBatch
				->getItemLabels( $itemId )
				->getLabelInLanguage( $languageCode )
				?->text;
		} );
	}
}
