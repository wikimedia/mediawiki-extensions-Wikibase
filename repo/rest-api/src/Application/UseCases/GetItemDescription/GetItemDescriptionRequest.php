<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription;

/**
 * @license GPL-2.0-or-later
 */
class GetItemDescriptionRequest {

	private string $itemId;
	private string $languageCode;

	public function __construct( string $itemId, string $languageCode ) {
		$this->itemId = $itemId;
		$this->languageCode = $languageCode;
	}

	public function getItemId(): string {
		return $this->itemId;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

}
