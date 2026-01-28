<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemDescriptions;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemDescriptionsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemDescriptionsRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemRedirectResolver;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemDescriptions {

	public function __construct(
		private readonly BatchItemDescriptionsRetriever $retriever,
		private readonly ItemRedirectResolver $redirectResolver,
	) {
	}

	/**
	 *  For redirected Items, the descriptions of the redirect target will be returned.
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetItemDescriptionsRequest $request ): BatchGetItemDescriptionsResponse {
		$requestedIds = array_map(
			fn( string $id ) => new ItemId( $id ),
			$request->itemIds,
		);
		$resolvedIds = array_map( $this->redirectResolver->resolveRedirect( ... ), $requestedIds );
		$fetchedDescriptions = $this->retriever->getItemDescriptions(
			array_unique( $resolvedIds ),
			$request->languageCodes
		);

		return new BatchGetItemDescriptionsResponse(
			$this->getDescriptionsByRequestedIds( $requestedIds, $resolvedIds, $fetchedDescriptions )
		);
	}

	private function getDescriptionsByRequestedIds(
		array $requestedIds,
		array $resolvedIds,
		ItemDescriptionsBatch $fetchedDescriptions
	): ItemDescriptionsBatch {
		$batch = [];
		foreach ( $requestedIds as $i => $id ) {
			$batch[$id->getSerialization()] = $fetchedDescriptions->getItemDescriptions( $resolvedIds[$i] );
		}

		return new ItemDescriptionsBatch( $batch );
	}
}
