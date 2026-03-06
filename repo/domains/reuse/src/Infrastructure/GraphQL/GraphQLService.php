<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Error\SyntaxError;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use MediaWiki\Config\Config;
use Psr\Log\LoggerInterface;
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
		private readonly LoggerInterface $logger,
		private readonly StatsFactory $stats,
		private readonly GraphQLTracking $tracking,
	) {
		$this->queryComplexityRule = new QueryComplexityRule( self::MAX_QUERY_COMPLEXITY );
	}

	public function query( string $query, array $variables = [], ?string $operationName = null ): array {
		if ( trim( $query ) === '' ) {
			$this->trackValidationError( GraphQLErrorType::MISSING_QUERY->name );

			return $this->formatErrorResponse( [ 'message' => "The 'query' field is required and must not be empty" ] );
		}

		try {
			$parsedQuery = Parser::parse( $query );
		} catch ( SyntaxError $e ) {
			$this->trackValidationError( GraphQLErrorType::INVALID_QUERY->name );

			$formattedError = FormattedError::createFromException( $e );
			$formattedError['message'] = 'Invalid query - ' . $e->getMessage();

			return $this->formatErrorResponse( $formattedError );
		}

		$context = new QueryContext();
		$result = GraphQL::executeQuery(
			$this->schema,
			$parsedQuery,
			contextValue: $context,
			variableValues: $variables,
			operationName: $operationName,
			validationRules: [
				...GraphQL::getStandardValidationRules(),
				$this->queryComplexityRule,
			],
		)->setErrorsHandler( function ( array $errors, callable $formatter ): array {
			$this->tracking->trackErrors( $this->queryComplexityRule, $errors );
			$this->logUnexpectedErrors( $errors );
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

		$this->tracking->trackUsage( $output, $parsedQuery, $operationName );
		return $output;
	}

	private function incrementHitMetric( string $status ): void {
		$this->stats->getCounter( 'wikibase_graphql_hit_total' )
			->setLabel( 'status', $status )
			->increment();
	}

	public function trackValidationError( string $errorType ): void {
		$this->stats->getCounter( 'wikibase_graphql_error_total' )
			->setLabel( 'type', $errorType )
			->increment();
		$this->incrementHitMetric( 'error' );
	}

	private function formatErrorResponse( array $error ): array {
		return [ 'errors' => [ $error ] ];
	}

	private function logUnexpectedErrors( array $errors ): void {
		/**
		 * Exceptions thrown in the query execution process get caught within {@link GraphQL::executeQuery} and rethrown as {@link Error}
		 * wrapping the original exception. Expected exceptions thrown within our code extend {@link GraphQLError}, so any other type of
		 * exception is unexpected and should be logged.
		 */
		foreach ( $errors as $error ) {
			/** @var Error $error */
			$previousError = $error->getPrevious();
			$isUnexpected = $previousError && !( $previousError instanceof GraphQLError );
			if ( $isUnexpected ) {
				$this->logger->error( $previousError->getMessage(), [
					'trace' => $previousError->getTraceAsString(),
				] );
			}
		}
	}
}
