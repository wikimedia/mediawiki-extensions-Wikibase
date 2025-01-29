<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyStatementsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatements {

	private GetPropertyStatementsValidator $validator;
	private PropertyStatementsRetriever $statementsRetriever;
	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;

	public function __construct(
		GetPropertyStatementsValidator $validator,
		PropertyStatementsRetriever $statementsRetriever,
		GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata
	) {
		$this->validator = $validator;
		$this->statementsRetriever = $statementsRetriever;
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyStatementsRequest $request ): GetPropertyStatementsResponse {
		$deserializedRequest = $this->validator->validateAndDeserialize( $request );

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $deserializedRequest->getPropertyId() );

		return new GetPropertyStatementsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property exists
			$this->statementsRetriever->getStatements( $deserializedRequest->getPropertyId(), $deserializedRequest->getPropertyIdFilter() ),
			$lastModified,
			$revisionId,
		);
	}

}
