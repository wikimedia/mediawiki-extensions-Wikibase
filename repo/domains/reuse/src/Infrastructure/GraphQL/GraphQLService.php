<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use MediaWiki\Config\Config;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLService {
	public const LOAD_ITEM_COMPLEXITY = 10;
	public const MAX_QUERY_COMPLEXITY = self::LOAD_ITEM_COMPLEXITY * 50;
	public const SEARCH_ITEMS_COMPLEXITY = self::MAX_QUERY_COMPLEXITY;

	private QueryComplexityRule $queryComplexityRule;

	public function __construct(
		private readonly Schema $schema,
		private readonly Config $config,
		private readonly StatsFactory $stats,
		private readonly GraphQLFieldCollector $graphQLFieldCollector,
	) {
		$this->queryComplexityRule = new QueryComplexityRule( self::MAX_QUERY_COMPLEXITY );
	}

	public function query( string $query, array $variables = [], ?string $operationName = null ): array {
		$context = new QueryContext();
		try {
			$result = GraphQL::executeQuery(
				$this->schema,
				$query,
				contextValue: $context,
				variableValues: $variables,
				operationName: $operationName,
				validationRules: [
					...GraphQL::getStandardValidationRules(),
					$this->queryComplexityRule,
				],
			)->setErrorsHandler( function ( array $errors, callable $formatter ): array {
				$this->trackErrors( $errors );
				return array_map( $formatter, $errors );
			} );
			if ( $context->redirects ) {
				$result->extensions[ QueryContext::KEY_MESSAGE ] = QueryContext::MESSAGE_REDIRECTS;
				$result->extensions[ QueryContext::KEY_REDIRECTS ] = $context->redirects;
			}
			$includeDebugInfo = DebugFlag::INCLUDE_TRACE | DebugFlag::INCLUDE_DEBUG_MESSAGE;
			$output = $result->toArray(
				$this->config->get( 'ShowExceptionDetails' ) ? $includeDebugInfo : DebugFlag::NONE
			);
		} catch ( Exception $e ) {
			$output = [
				'errors' => [
					[
						'message' => $e->getMessage(),
					],
				],
			];
		}

		$this->trackUsage( $output, $query, $operationName );

		return $output;
	}

	private function trackUsage( array $output, string $query, ?string $operationName ): void {
		if ( !( isset( $output['data'] ) ) ) {
			$this->incrementHitMetric( 'error' );
			return;
		}

		$usedFields = $this->graphQLFieldCollector->getRequestedFieldPaths( $query, $operationName );
		$isIntrospectionQuery = !array_intersect( $this->schema->fieldNames, $usedFields );
		if ( $isIntrospectionQuery ) {
			$this->incrementHitMetric( 'introspection' );
			return;
		}

		// field usage is tracked for (partial) success, but not introspection-only or error-only
		$this->trackFieldUsage( $usedFields );

		if ( isset( $output['errors'] ) ) {
			$this->incrementHitMetric( 'partial_success' );
		} else {
			$this->incrementHitMetric( 'success' );
		}
	}

	private function incrementHitMetric( string $status ): void {
		$this->stats->getCounter( 'wikibase_graphql_hit_total' )
			->setLabel( 'status', $status )
			->increment();
	}

	private function trackErrors( array $errors ): void {
		$errorTypes = array_unique( array_map(
			fn( Error $e ) => $this->getErrorType( $e )->name,
			$errors,
		) );

		foreach ( $errorTypes as $type ) {
			$this->stats->getCounter( 'wikibase_graphql_error_total' )
				->setLabel( 'type', $type )
				->increment();
		}
	}

	private function getErrorType( Error $error ): GraphQLErrorType {
		if ( $this->queryComplexityRule->wasChecked()
			&& $this->queryComplexityRule->getQueryComplexity() > $this->queryComplexityRule->getMaxQueryComplexity() ) {
			return GraphQLErrorType::QUERY_TOO_COMPLEX;
		}

		$previousError = $error->getPrevious();
		if ( $previousError instanceof GraphQLError ) {
			return $previousError->type;
		}

		// If there is no previous error, it means that the GraphQL engine itself rejected the query
		// e.g. because the query was malformed, or an invalid field or operation name.
		if ( $previousError === null ) {
			return GraphQLErrorType::INVALID_QUERY;
		}

		return GraphQLErrorType::UNKNOWN;
	}

	private function trackFieldUsage( array $fields ): void {
		foreach ( $fields as $field ) {
			$this->stats->getCounter( 'wikibase_graphql_field_usage_total' )
				->setLabel( 'field', $field )
				->increment();
		}
	}
}
