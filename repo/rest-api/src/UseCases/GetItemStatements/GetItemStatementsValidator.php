<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemStatements;

use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemStatementsValidator {

	public const SOURCE_ITEM_ID = 'item ID';

	private ItemIdValidator $itemIdValidator;

	public function __construct( ItemIdValidator $itemIdValidator ) {
		$this->itemIdValidator = $itemIdValidator;
	}

	public function validate( GetItemStatementsRequest $request ): ?ValidationError {
		return $this->itemIdValidator->validate( $request->getItemId(), self::SOURCE_ITEM_ID );
	}

}
