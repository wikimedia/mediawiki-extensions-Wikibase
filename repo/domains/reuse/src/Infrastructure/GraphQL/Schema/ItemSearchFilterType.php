<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchFilterType extends InputObjectType {

	public function __construct( Types $types ) {
		$searchCondition = $types->getItemSearchConditionType();
		$orFieldDefinition = [
			'type' => Type::listOf( Type::nonNull( $searchCondition ) ),
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'description' => 'Combine multiple conditions using OR operator. Requires at least two conditions, one of which must match. Cannot be used together with any other field.',
		];
		$notFieldDefinition = [
			'type' => $searchCondition,
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'description' => 'Search for items that do not have a certain statement using NOT operator. Supports a single property/value condition. Cannot be used together with any other field.',
		];

		parent::__construct( [
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'description' => 'Filter used to match items by their statements. Supports simple property/value matching or combining multiple filters with an operator.',
			'fields' => [
				'and' => [
					'type' => Type::listOf( Type::nonNull( new InputObjectType( [
						'name' => 'AndOperationCondition',
						// phpcs:ignore Generic.Files.LineLength.TooLong
						'description' => 'A condition within an AND operation used in item search. Supports simple property/value matching or combining multiple filters with OR.',
						'fields' => [
							'or' => $orFieldDefinition,
							'not' => $notFieldDefinition,
							$searchCondition->getField( 'property' ),
							$searchCondition->getField( 'value' ),
						],
					] ) ) ),
					// phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => 'Combine multiple conditions using AND operator. Requires at least two conditions, all of which must match. Cannot be used together with any other field.',
				],
				'or' => $orFieldDefinition,
				'not' => $notFieldDefinition,
				$searchCondition->getField( 'property' ),
				$searchCondition->getField( 'value' ),
			],
		] );
	}
}
