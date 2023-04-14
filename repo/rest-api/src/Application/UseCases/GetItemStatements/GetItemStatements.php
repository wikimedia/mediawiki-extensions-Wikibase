<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatements {

	private GetItemStatementsValidator $validator;
	private ItemStatementsRetriever $statementsRetriever;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;

	public function __construct(
		GetItemStatementsValidator $validator,
		ItemStatementsRetriever $statementsRetriever,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever
	) {
		$this->validator = $validator;
		$this->statementsRetriever = $statementsRetriever;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
	}

	/**
	 * @throws ItemRedirect
	 * @throws UseCaseError
	 */
	public function execute( GetItemStatementsRequest $request ): GetItemStatementsResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );
		$statementPropertyId = $request->getStatementPropertyId();
		$requestedStatementPropertyId = $statementPropertyId
			? new NumericPropertyId( $statementPropertyId )
			: null;

		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			throw new UseCaseError(
				UseCaseError::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		} elseif ( $latestRevisionMetadata->isRedirect() ) {
			throw new ItemRedirect( $latestRevisionMetadata->getRedirectTarget()->getSerialization() );
		}

		return new GetItemStatementsResponse(
			$this->statementsRetriever->getStatements( $itemId, $requestedStatementPropertyId ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
