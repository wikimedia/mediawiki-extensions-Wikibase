<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatements {

	private $statementsRetriever;
	private $revisionMetadataRetriever;
	private $serializer;

	public function __construct(
		ItemStatementsRetriever $statementsRetriever,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever,
		StatementListSerializer $serializer
	) {
		$this->statementsRetriever = $statementsRetriever;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
		$this->serializer = $serializer;
	}

	public function execute( GetItemStatementsRequest $request ): GetItemStatementsSuccessResponse {
		$itemId = new ItemId( $request->getItemId() );

		$latestRevisionMetadata = $this->revisionMetadataRetriever->getLatestRevisionMetadata( $itemId );

		return new GetItemStatementsSuccessResponse(
			$this->serializer->serialize( $this->statementsRetriever->getStatements( $itemId ) ),
			$latestRevisionMetadata->getRevisionTimestamp(),
			$latestRevisionMetadata->getRevisionId()
		);
	}

}
