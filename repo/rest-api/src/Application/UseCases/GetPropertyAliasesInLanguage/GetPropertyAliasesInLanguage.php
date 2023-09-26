<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguage {

	private PropertyAliasesInLanguageRetriever $propertyAliasesRetriever;

	public function __construct( PropertyAliasesInLanguageRetriever $propertyAliasesRetriever ) {
		$this->propertyAliasesRetriever = $propertyAliasesRetriever;
	}

	public function execute( GetPropertyAliasesInLanguageRequest $request ): GetPropertyAliasesInLanguageResponse {
		return new GetPropertyAliasesInLanguageResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->propertyAliasesRetriever->getAliasesInLanguage(
				new NumericPropertyId( $request->getPropertyId() ),
				$request->getLanguageCode()
			)
		);
	}

}
