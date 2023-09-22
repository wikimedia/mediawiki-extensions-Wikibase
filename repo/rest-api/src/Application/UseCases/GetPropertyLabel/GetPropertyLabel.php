<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabel {

	private PropertyLabelRetriever $labelRetriever;

	public function __construct( PropertyLabelRetriever $labelRetriever ) {
		$this->labelRetriever = $labelRetriever;
	}

	public function execute( GetPropertyLabelRequest $request ): GetPropertyLabelResponse {
		return new GetPropertyLabelResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->labelRetriever->getLabel( new NumericPropertyId( $request->getPropertyId() ), $request->getLanguageCode() )
		);
	}

}
