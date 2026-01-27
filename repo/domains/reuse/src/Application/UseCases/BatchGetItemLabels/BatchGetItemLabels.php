<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases\BatchGetItemLabels;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ItemLabelsBatch;
use Wikibase\Repo\Domains\Reuse\Domain\Services\BatchItemLabelsRetriever;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemRedirectResolver;

/**
 * @license GPL-2.0-or-later
 */
class BatchGetItemLabels {

	public function __construct(
		private readonly BatchItemLabelsRetriever $retriever,
		private readonly ItemRedirectResolver $redirectResolver,
	) {
	}

	/**
	 * For redirected Items, the labels of the redirect target will be returned.
	 *
	 * This use case does not validate its request object.
	 * Validation must be added before it can be used in a context where the request is created from user input.
	 */
	public function execute( BatchGetItemLabelsRequest $request ): BatchGetItemLabelsResponse {
		$requestedIds = array_map(
			fn( string $id ) => new ItemId( $id ),
			$request->itemIds,
		);
		$resolvedIds = array_map( $this->redirectResolver->resolveRedirect( ... ), $requestedIds );
		$fetchedLabels = $this->retriever->getItemLabels(
			array_unique( $resolvedIds ),
			$request->languageCodes
		);

		return new BatchGetItemLabelsResponse( $this->getLabelsByRequestedIds( $requestedIds, $resolvedIds, $fetchedLabels ) );
	}

	private function getLabelsByRequestedIds(
		array $requestedIds,
		array $resolvedIds,
		ItemLabelsBatch $fetchedLabels
	): ItemLabelsBatch {
		$batch = [];
		foreach ( $requestedIds as $i => $id ) {
			$batch[$id->getSerialization()] = $fetchedLabels->getItemLabels( $resolvedIds[$i] );
		}

		return new ItemLabelsBatch( $batch );
	}
}
