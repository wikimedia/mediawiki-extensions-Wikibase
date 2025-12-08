<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptions;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions\BatchGetItemDescriptionsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionsResolver {
	private array $itemsToFetch = [];
	private array $languagesToFetch = [];
	private ?ItemDescriptionsBatch $descriptionsBatch = null;

	public function __construct(
		private readonly BatchGetItemDescriptions $batchGetItemDescriptions
	) {
	}

	public function resolve( ItemId $itemId, string $languageCode ): Deferred {
		$this->itemsToFetch[] = $itemId->getSerialization();
		$this->languagesToFetch[] = $languageCode;

		return new Deferred( function() use ( $itemId, $languageCode ) {
			if ( !$this->descriptionsBatch ) {
				$this->descriptionsBatch = $this->batchGetItemDescriptions
					->execute( new BatchGetItemDescriptionsRequest(
						array_values( array_unique( $this->itemsToFetch ) ),
						array_values( array_unique( $this->languagesToFetch ) ),
					) )
					->batch;
			}

			return $this->descriptionsBatch
				->getItemDescriptions( $itemId )
				->getDescriptionInLanguage( $languageCode )
				?->text;
		} );
	}
}
