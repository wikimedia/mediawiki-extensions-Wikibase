<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\LabelLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\UseCaseRequest;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelWithFallbackRequest implements UseCaseRequest, ItemIdRequest, LabelLanguageCodeRequest {

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
