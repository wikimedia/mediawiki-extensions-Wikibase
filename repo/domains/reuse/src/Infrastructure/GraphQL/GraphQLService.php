<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Validator\DocumentValidator;
use MediaWiki\Config\Config;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLService {
	public const ITEM_FIELD_COMPLEXITY = 10;
	public const MAX_QUERY_COMPLEXITY = self::ITEM_FIELD_COMPLEXITY * 50;

	public function __construct(
		private readonly Schema $schema,
		private readonly Config $config,
	) {
	}

	public function query( string $query ): array {
		DocumentValidator::addRule( new QueryComplexityRule( self::MAX_QUERY_COMPLEXITY ) );

		try {
			$result = GraphQL::executeQuery( $this->schema, $query );
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

		return $output;
	}
}
