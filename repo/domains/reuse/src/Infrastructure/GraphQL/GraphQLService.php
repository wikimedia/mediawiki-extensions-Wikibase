<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL;

use Exception;
use GraphQL\GraphQL;
use Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema\Schema;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLService {

	public function __construct( private Schema $schema ) {
	}

	public function query( string $query ): array {
		try {
			$result = GraphQL::executeQuery( $this->schema, $query, [] );
			$output = $result->toArray();
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
