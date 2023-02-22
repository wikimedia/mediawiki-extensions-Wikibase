<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\ItemRedirectException;
use Wikibase\Repo\RestApi\UseCases\UseCaseException;

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
	 * @throws ItemRedirectException
	 * @throws UseCaseException
	 */
	public function execute( GetItemStatementsRequest $request ): GetItemStatementsResponse {
		$this->validator->assertValidRequest( $request );

		$itemId = new ItemId( $request->getItemId() );
		$requestedStatementPropertyId = $request->getStatementPropertyId()
			? new NumericPropertyId( $request->getStatementPropertyId() )
			: null;

		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		if ( !$latestRevisionMetadata->itemExists() ) {
			throw new UseCaseException(
				ErrorResponse::ITEM_NOT_FOUND,
				"Could not find an item with the ID: {$request->getItemId()}"
			);
		} elseif ( $latestRevisionMetadata->isRedirect() ) {
			throw new ItemRedirectException( $latestRevisionMetadata->getRedirectTarget()->getSerialization() );
		}

		return new GetItemStatementsResponse(
			$this->statementsRetriever->getStatements( $itemId, $requestedStatementPropertyId ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
