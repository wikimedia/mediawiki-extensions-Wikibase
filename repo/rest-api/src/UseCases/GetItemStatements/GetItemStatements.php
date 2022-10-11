<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectResponse;

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
	 * @return GetItemStatementsSuccessResponse|ItemRedirectResponse|GetItemStatementsErrorResponse
	 */
	public function execute( GetItemStatementsRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return GetItemStatementsErrorResponse::newFromValidationError( $validationError );
		}

		$itemId = new ItemId( $request->getItemId() );

		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			return new GetItemStatementsErrorResponse(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		} elseif ( $latestRevisionMetadata->isRedirect() ) {
			return new ItemRedirectResponse( $latestRevisionMetadata->getRedirectTarget()->getSerialization() );
		}

		return new GetItemStatementsSuccessResponse(
			$this->statementsRetriever->getStatements( $itemId ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
