<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LabelLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyLabelRequest implements UseCaseRequest, PropertyIdRequest, LabelLanguageCodeRequest {

	private string $propertyId;
	private string $languageCode;

	public function __construct( string $propertyId, string $languageCode ) {
		$this->propertyId = $propertyId;
		$this->languageCode = $languageCode;
	}

	public function getPropertyId(): string {
		return $this->propertyId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

}
