<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema as GraphQLSchema;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class Schema extends GraphQLSchema {

	public function __construct(
		private ContentLanguages $labelLanguages,
		private LabelsResolver $labelsResolver,
	) {
		parent::__construct( [
			'query' => new ObjectType( [
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
			] ),
		] );
	}

	private function itemType(): ObjectType {
		return new ObjectType( [
			'name' => 'Item',
			'fields' => [
				'id' => [
					'type' => Type::string(),
				],
				'labels' => [
					'type' => $this->labelsType(),
					'resolve' => fn( array $rootValue, array $args, $context, ResolveInfo $info ) => $this->labelsResolver->fetchLabels(
						$rootValue,
						$info
					),
				],
			],
		] );
	}

	private function labelsType(): ObjectType {
		return new ObjectType( [
			'name' => 'Labels',
			'fields' => array_fill_keys(
				array_map(
					// The GraphQL schema does not allow dashes in field names, so we replace them
					// with underscores.
					fn( string $languageCode ) => str_replace( '-', '_', $languageCode ),
					$this->labelLanguages->getLanguages()
				),
				[ 'type' => Type::string() ]
			),
		] );
	}

}
