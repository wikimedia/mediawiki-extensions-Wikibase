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

	private ?ObjectType $labelsType = null;

	public function __construct(
		private ContentLanguages $labelLanguages,
		private LabelsResolver $labelsResolver,
		private StatementsResolver $statementsResolver,
		private ItemResolver $itemResolver,
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
						'resolve' => fn( $rootValue, array $args ) => $this->itemResolver->fetchItem( $args['id'] ),
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
					'type' => Type::nonNull( Type::string() ),
				],
				'labels' => [
					'type' => $this->labelsType(),
					'resolve' => fn( array $rootValue, array $args, $context, ResolveInfo $info ) => $this->labelsResolver
						->fetchLabels( $rootValue, $info ),
				],
				'statements' => [
					'type' => Type::listOf( $this->statementType() ), // @phan-suppress-current-line PhanUndeclaredInvokeInCallable
					'resolve' => $this->statementsResolver->fetchStatements( ... ),
				],
			],
		] );
	}

	private function labelsType(): ObjectType {
		// In order to reuse a type in multiple places, it needs to be the same type object instance.
		$this->labelsType ??= new ObjectType( [
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

		return $this->labelsType;
	}

	private function statementType(): ObjectType {
		return new ObjectType( [
			'name' => 'Statement',
			'fields' => [
				'property' => new ObjectType( [
					'name' => 'StatementProperty',
					'fields' => [
						'id' => Type::nonNull( Type::string() ),
						'labels' => [
							'type' => $this->labelsType(),
							'resolve' => fn( array $rootValue, array $args, $context, ResolveInfo $info ) => $this->labelsResolver
								->fetchLabels( $rootValue, $info ),
						],
					],
				] ),
				'value' => new ObjectType( [
					'name' => 'StatementValue',
					'fields' => [
						'content' => Type::nonNull( Type::string() ),
					],
				] ),
			],
		] );
	}

}
