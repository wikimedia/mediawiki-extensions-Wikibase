<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLQueryService {

	public function query( string $query ): array {
		$queryType = new ObjectType( [
			'name' => 'Query',
			'fields' => [
				'item' => [
					'type' => $this->itemType(),
					'args' => [
						'id' => Type::nonNull( Type::string() ),
					],
					'resolve' => fn( $rootValue, array $args ) => [ 'id' => $args['id'] ],
				],
			],
		] );

		$schema = new Schema( [
			'query' => $queryType,
		] );

		try {
			$result = GraphQL::executeQuery( $schema, $query, [] );
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

	private function itemType(): ObjectType {
		return new ObjectType( [
			'name' => 'Item',
			'fields' => [
				'id' => [
					'type' => Type::string(),
				],
			],
		] );
	}

}
