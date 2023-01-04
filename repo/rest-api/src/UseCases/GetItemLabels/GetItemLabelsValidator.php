<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemLabels;

use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemLabelsValidator {

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	public function validate( GetItemLabelsRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId() );
	}
}
