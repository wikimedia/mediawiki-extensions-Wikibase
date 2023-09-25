<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyDescriptionRequest {

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
