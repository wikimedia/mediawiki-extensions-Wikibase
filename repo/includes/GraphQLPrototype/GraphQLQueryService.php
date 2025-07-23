<?php

namespace Wikibase\Repo\GraphQLPrototype;

use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class GraphQLQueryService {

	public function __construct(
		private ContentLanguages $labelLanguages,
		private PrefetchingTermLookup $termLookup
	) {
	}

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
				'labels' => [
					'type' => $this->labelsType(),
					'resolve' => fn( array $rootValue, array $args, $context, ResolveInfo $info ) => $this->fetchLabels(
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

	private function fetchLabels( array $rootValue, ResolveInfo $info ): array {
		$itemId = new ItemId( $rootValue['id'] );
		$languageCodes = array_keys( $info->getFieldSelection() );

		$this->termLookup->prefetchTerms(
			[ $itemId ],
			[ TermTypes::TYPE_LABEL ],
			$languageCodes
		);

		return $this->termLookup->getLabels( $itemId, $languageCodes );
	}

}
