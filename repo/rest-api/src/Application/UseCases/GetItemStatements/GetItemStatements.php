<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatements {

	private GetItemStatementsValidator $validator;
	private ItemStatementsRetriever $statementsRetriever;
	private GetLatestItemRevisionMetadata $getLatestRevisionMetadata;

	public function __construct(
		GetItemStatementsValidator $validator,
		ItemStatementsRetriever $statementsRetriever,
		GetLatestItemRevisionMetadata $getLatestRevisionMetadata
	) {
		$this->validator = $validator;
		$this->statementsRetriever = $statementsRetriever;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
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

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $itemId );

		return new GetItemStatementsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Item validated and exists
			$this->statementsRetriever->getStatements( $itemId, $requestedStatementPropertyId ),
			$lastModified,
			$revisionId,
		);
	}

}
