<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class UnexpectedError implements ErrorResponse {

	public function getCode(): string {
		return ErrorResponse::UNEXPECTED_ERROR;
	}

	public function getMessage(): string {
		return 'Unexpected error';
	}
}
