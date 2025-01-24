<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels;

use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyLabelsRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabels {

	private GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata;
	private PropertyLabelsRetriever $propertyLabelsRetriever;
	private GetPropertyLabelsValidator $validator;

	public function __construct(
		GetLatestPropertyRevisionMetadata $getLatestRevisionMetadata,
		PropertyLabelsRetriever $propertyLabelsRetriever,
		GetPropertyLabelsValidator $validator
	) {
		$this->getLatestRevisionMetadata = $getLatestRevisionMetadata;
		$this->propertyLabelsRetriever = $propertyLabelsRetriever;
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function execute( GetPropertyLabelsRequest $request ): GetPropertyLabelsResponse {
		$propertyId = $this->validator->validateAndDeserialize( $request )->getPropertyId();

		[ $revisionId, $lastModified ] = $this->getLatestRevisionMetadata->execute( $propertyId );

		return new GetPropertyLabelsResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Property validated and exists
			$this->propertyLabelsRetriever->getLabels( $propertyId ),
			$lastModified,
			$revisionId,
		);
	}
}
