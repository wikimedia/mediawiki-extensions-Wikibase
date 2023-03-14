<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Presentation\Presenters;

/**
 * @license GPL-2.0-or-later
 */
class ErrorJsonPresenter {

	public function getJson( string $errorCode, string $errorMessage, array $errorContext = null ): string {
		return json_encode(
			// use array_filter to remove 'context' from array if $error->getContext() is NULL
			array_filter( [
				'code' => $errorCode,
				'message' => $errorMessage,
				'context' => $errorContext,
			] ),
			JSON_UNESCAPED_SLASHES
		);
	}

}
