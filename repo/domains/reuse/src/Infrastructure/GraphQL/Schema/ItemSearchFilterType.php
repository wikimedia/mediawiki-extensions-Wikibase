<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchFilterType extends InputObjectType {

	public function __construct( Types $types ) {
		parent::__construct(
			[
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'description' => 'Filter used to match items by their statements. Supports simple property/value matching or combining multiple filters with AND.',
				'fields' => [
					'and' => [
						// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
						'type' => Type::listOf( Type::nonNull( $this ) ),
						// phpcs:ignore Generic.Files.LineLength.TooLong
						'description' => 'Combine multiple filters using AND operator. All filters must match, requires at least two filter and cannot be used together with the property field.',
					],
					'property' => $types->getPropertyIdType(),
					'value' => Type::string(),
				],
			]
		);
	}

}
