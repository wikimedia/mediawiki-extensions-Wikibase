<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLError;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Errors\GraphQLErrorType;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLTracking {

	public function __construct(
		private readonly Schema $schema,
		private readonly StatsFactory $stats,
		private readonly GraphQLFieldCollector $graphQLFieldCollector,
	) {
	}

	public function trackUsage( array $output, DocumentNode $doc, ?string $operationName ): void {
		if ( !( isset( $output[ 'data' ] ) ) ) {
			$this->incrementHitMetric( 'error' );
			return;
		}

		$usedFields = $this->graphQLFieldCollector->getRequestedFieldPaths( $doc, $operationName );
		$isIntrospectionQuery = !array_intersect( $this->schema->fieldNames, $usedFields );
		if ( $isIntrospectionQuery ) {
			$this->incrementHitMetric( 'introspection' );
			return;
		}

		// field usage is tracked for (partial) success, but not introspection-only or error-only
		$this->trackFieldUsage( $usedFields );

		if ( isset( $output[ 'errors' ] ) ) {
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

	private function trackFieldUsage( array $fields ): void {
		foreach ( $fields as $field ) {
			$this->stats->getCounter( 'wikibase_graphql_field_usage_total' )
				->setLabel( 'field', $field )
				->increment();
		}
	}

	public function trackErrors( QueryComplexityRule $complexityRule, array $errors ): void {
		$errorTypes = array_unique( array_map(
			fn( Error $e ) => $this->getErrorType( $complexityRule, $e )->name,
			$errors,
		) );

		foreach ( $errorTypes as $type ) {
			$this->stats->getCounter( 'wikibase_graphql_error_total' )
				->setLabel( 'type', $type )
				->increment();
		}
	}

	private function getErrorType( QueryComplexityRule $queryComplexityRule, Error $error ): GraphQLErrorType {
		if ( $queryComplexityRule->wasChecked()
			 && $queryComplexityRule->getQueryComplexity() > $queryComplexityRule->getMaxQueryComplexity() ) {
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

	public function trackValidationError( string $errorType ): void {
		$this->stats->getCounter( 'wikibase_graphql_error_total' )
			->setLabel( 'type', $errorType )
			->increment();
		$this->incrementHitMetric( 'error' );
	}
}
