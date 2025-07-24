<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLQueryService {

	public function __construct( private Schema $schema ) {
	}

	public function query( string $query ): array {
		try {
			$result = GraphQL::executeQuery( $this->schema, $query, [] );
			$output = $result->toArray();
		} catch ( \Exception $e ) {
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
