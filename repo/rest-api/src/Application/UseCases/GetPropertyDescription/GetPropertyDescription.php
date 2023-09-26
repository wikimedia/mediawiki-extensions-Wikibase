<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescription {

	private PropertyDescriptionRetriever $descriptionRetriever;

	public function __construct( PropertyDescriptionRetriever $descriptionRetriever ) {
		$this->descriptionRetriever = $descriptionRetriever;
	}

	public function execute( GetPropertyDescriptionRequest $request ): GetPropertyDescriptionResponse {
		return new GetPropertyDescriptionResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->descriptionRetriever->getDescription(
				new NumericPropertyId( $request->getPropertyId() ),
				$request->getLanguageCode()
			)
		);
	}
}
