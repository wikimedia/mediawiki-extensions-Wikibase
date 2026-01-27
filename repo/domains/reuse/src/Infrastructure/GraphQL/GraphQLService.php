<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use MediaWiki\Config\Config;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLService {
	public const LOAD_ITEM_COMPLEXITY = 10;
	public const MAX_QUERY_COMPLEXITY = self::LOAD_ITEM_COMPLEXITY * 50;
	public const SEARCH_ITEMS_COMPLEXITY = self::MAX_QUERY_COMPLEXITY;

	public function __construct(
		private readonly Schema $schema,
		private readonly Config $config,
		private readonly StatsFactory $stats,
	) {
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
					new QueryComplexityRule( self::MAX_QUERY_COMPLEXITY ),
				],
			);
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

		$this->trackGraphQLHit( $output );

		return $output;
	}

	private function trackGraphQLHit( array $output ): void {
		$hasData = isset( $output['data'] );
		$hasError = isset( $output['errors'] );
		if ( $hasData && $hasError ) {
			$this->incrementHitMetric( 'partial_success' );
		} elseif ( $hasData ) {
			$this->incrementHitMetric( 'success' );
		} else {
			$this->incrementHitMetric( 'error' );
		}
	}

	private function incrementHitMetric( string $status ): void {
		$this->stats->getCounter( 'wikibase_graphql_hit_total' )
			->setLabel( 'status', $status )
			->increment();
	}
}
