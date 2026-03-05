<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorResponse;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation\InvalidResult;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation\ValidResult;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLQueryValidator {

	public static function validate( string $query ): InvalidResult|ValidResult {
		if ( trim( $query ) === '' ) {
			return new InvalidResult(
				GraphQLErrorResponse::fromArray( [ 'message' => "The 'query' field is required and must not be empty" ] ),
				GraphQLErrorType::MISSING_QUERY->name,
			);
		}

		try {
			$documentNode = Parser::parse( $query );
		} catch ( SyntaxError $e ) {
			return new InvalidResult(
				GraphQLErrorResponse::fromSyntaxError( $e ),
				GraphQLErrorType::INVALID_QUERY->name,
			);
		}

		return new ValidResult( $documentNode );
	}

}
