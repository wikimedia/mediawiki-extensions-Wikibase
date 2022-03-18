<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItem;

use Wikibase\Repo\RestApi\UseCases\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class GetItemValidationResult {
	public const SOURCE_ITEM_ID = 'item ID';

	private $error = null;

	public function setError( ValidationError $error ): void {
		$this->error = $error;
	}

	public function getError(): ValidationError {
		return $this->error;
	}

	public function hasError(): bool {
		return $this->error !== null;
	}

}
