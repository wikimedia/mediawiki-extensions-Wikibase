<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @license GPL-2.0-or-later
 */
class ItemSearchFilterType extends InputObjectType {

	public function __construct( Types $types ) {
		// @phan-suppress-next-line PhanUndeclaredInvokeInCallable
		parent::__construct(
			[
				'fields' => [
					'and' => Type::listOf( Type::nonNull( $this ) ),
					'property' => $types->getPropertyIdType(),
					'value' => Type::string(),
				],
			]
		);
	}

}
