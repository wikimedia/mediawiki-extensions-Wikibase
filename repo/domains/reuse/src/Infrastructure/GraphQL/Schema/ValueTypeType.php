<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;

/**
 * @license GPL-2.0-or-later
 */
class ValueTypeType extends EnumType {
	public const VALUE = 'VALUE';
	public const SOME_VALUE = 'SOME_VALUE';
	public const NO_VALUE = 'NO_VALUE';

	public function __construct() {
		parent::__construct( [
			'values' => [ self::VALUE, self::SOME_VALUE, self::NO_VALUE ],
		] );
	}

}
