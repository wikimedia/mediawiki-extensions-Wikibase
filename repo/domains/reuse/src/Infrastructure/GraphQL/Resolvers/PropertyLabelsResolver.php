<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Resolvers;

use GraphQL\Deferred;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabels;
use Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetPropertyLabels\BatchGetPropertyLabelsRequest;
use Wikibase\Repo\Domains\Reuse\Domain\Model\PropertyLabelsBatch;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelsResolver {
	private array $propertiesToFetch = [];
	private array $languagesToFetch = [];
	private ?PropertyLabelsBatch $labelsBatch = null;

	public function __construct(
		private readonly BatchGetPropertyLabels $batchGetPropertyLabels
	) {
	}

	public function resolve( PropertyId $propertyId, string $languageCode ): Deferred {
		$this->propertiesToFetch[] = $propertyId->getSerialization();
		$this->languagesToFetch[] = $languageCode;

		return new Deferred( function() use ( $propertyId, $languageCode ) {
			if ( !$this->labelsBatch ) {
				$this->labelsBatch = $this->batchGetPropertyLabels
					->execute( new BatchGetPropertyLabelsRequest(
						array_values( array_unique( $this->propertiesToFetch ) ),
						array_values( array_unique( $this->languagesToFetch ) ),
					) )
					->batch;
			}

			return $this->labelsBatch
				->getPropertyLabels( $propertyId )
				->getLabelInLanguage( $languageCode )
				?->text;
		} );
	}
}
