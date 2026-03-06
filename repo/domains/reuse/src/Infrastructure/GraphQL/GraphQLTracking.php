<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Language\AST\DocumentNode;
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
}
