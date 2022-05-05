<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetaDataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatements {

	private $validator;
	private $statementsRetriever;
	private $revisionMetaDataRetriever;
	private $serializer;

	public function __construct(
		GetItemStatementsValidator $validator,
		ItemStatementsRetriever $statementsRetriever,
		ItemRevisionMetaDataRetriever $revisionMetaDataRetriever,
		StatementListSerializer $serializer
	) {
		$this->validator = $validator;
		$this->statementsRetriever = $statementsRetriever;
		$this->revisionMetaDataRetriever = $revisionMetaDataRetriever;
		$this->serializer = $serializer;
	}

	/**
	 * @return GetItemStatementsSuccessResponse|GetItemStatementsErrorResponse
	 */
	public function execute( GetItemStatementsRequest $request ) {
		$validationError = $this->validator->validate( $request );
		if ( $validationError ) {
			return GetItemStatementsErrorResponse::newFromValidationError( $validationError );
		}

		$itemId = new ItemId( $request->getItemId() );

		$latestRevisionMetaData = $this->revisionMetaDataRetriever->getLatestRevisionMetaData( $itemId );

		return new GetItemStatementsSuccessResponse(
			$this->serializer->serialize( $this->statementsRetriever->getStatements( $itemId ) ),
			$latestRevisionMetaData->getRevisionTimestamp(),
			$latestRevisionMetaData->getRevisionId()
		);
	}

}
