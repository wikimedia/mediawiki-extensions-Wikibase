<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

use Wikibase\Repo\RestApi\UseCases\ErrorResponse;

/**
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenter {

	public function getJson( ErrorResponse $error ): string {
		return json_encode(
			// use array_filter to remove 'context' from array if $error->getContext() is NULL
			array_filter( [
				'code' => $error->getCode(),
				'message' => $error->getMessage(),
				'context' => $error->getContext(),
			] ),
			JSON_UNESCAPED_SLASHES
		);
	}

}
