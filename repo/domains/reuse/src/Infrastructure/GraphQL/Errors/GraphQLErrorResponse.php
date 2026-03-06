<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors;

use GraphQL\Error\FormattedError;
use GraphQL\Error\SyntaxError;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLErrorResponse {

	public static function fromArray( array $error ): array {
		return [ 'errors' => [ $error ] ];
	}

	public static function fromSyntaxError( SyntaxError $e ): array {
		$formattedError = FormattedError::createFromException( $e );
		$formattedError['message'] = 'Invalid query - ' . $e->getMessage();

		return self::fromArray( $formattedError );
	}
}
