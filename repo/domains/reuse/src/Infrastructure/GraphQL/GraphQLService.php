<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use MediaWiki\Config\Config;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation\ValidResult;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLService {
	public const LOAD_ITEM_COMPLEXITY = 10;
	public const MAX_QUERY_COMPLEXITY = self::LOAD_ITEM_COMPLEXITY * 50;
	public const SEARCH_ITEMS_COMPLEXITY = self::MAX_QUERY_COMPLEXITY;
	public const LOOKUP_ITEM_COMPLEXITY = 10;

	private QueryComplexityRule $queryComplexityRule;

	public function __construct(
		private readonly Schema $schema,
		private readonly Config $config,
		private readonly GraphQLErrorLogger $errorLogger,
		private readonly GraphQLTracking $tracking,
	) {
		$this->queryComplexityRule = new QueryComplexityRule( self::MAX_QUERY_COMPLEXITY );
	}

	public function query( string $query, array $variables = [], ?string $operationName = null ): array {
		$validationResult = GraphQLQueryValidator::validate( $query );
		$context = new QueryContext();

		if ( $validationResult instanceof ValidResult ) {
			$parsedQuery = $validationResult->parsedQuery;
			$result = GraphQL::executeQuery(
				$this->schema,
				$validationResult->parsedQuery,
				contextValue: $context,
				variableValues: $variables,
				operationName: $operationName,
				validationRules: [
					...GraphQL::getStandardValidationRules(),
					$this->queryComplexityRule,
				],
			);
		} else {
			$parsedQuery = null;
			$result = new ExecutionResult( errors: [ $validationResult ] );
		}

		if ( $context->redirects ) {
			$result->extensions[ QueryContext::KEY_MESSAGE ] = QueryContext::MESSAGE_REDIRECTS;
			$result->extensions[ QueryContext::KEY_REDIRECTS ] = $context->redirects;
		}

		if ( $context->missingItemIds ) {
			$result->extensions[ QueryContext::KEY_MISSING_ITEMS ] = $context->missingItemIds;
			$result->extensions[ QueryContext::KEY_MESSAGE ] = sprintf(
				QueryContext::MESSAGE_MISSING_ITEMS,
				implode( ', ', $context->missingItemIds )
			);
		}

		$this->transformErrors( $result );
		$this->tracking->recordQueryMetrics( $result, $parsedQuery, $operationName );
		$this->errorLogger->logUnexpectedErrors( $result->errors );

		$includeDebugInfo = DebugFlag::INCLUDE_TRACE | DebugFlag::INCLUDE_DEBUG_MESSAGE;
		return $result->toArray(
			$this->config->get( 'ShowExceptionDetails' ) ? $includeDebugInfo : DebugFlag::NONE
		);
	}

	/**
	 * Transforms errors for easier processing by tracking/logging code
	 */
	private function transformErrors( ExecutionResult $result ): void {
		if ( count( $result->errors ) === 1 && $this->queryComplexityRule->wasViolated() ) {
			$result->errors = [ GraphQLError::queryTooComplex(
				$this->queryComplexityRule->getQueryComplexity(),
				$this->queryComplexityRule->getMaxQueryComplexity(),
			) ];
		}

		$result->errors = array_map(
			fn( Error $error ) => $error->getPrevious() instanceof GraphQLError ? $error->getPrevious() : $error,
			$result->errors,
		);
	}

}
