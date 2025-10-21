<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Schema;

use GraphQL\Type\Definition\EnumType;
use Wikibase\Repo\Domains\Reuse\Domain\Model\ValueType;

/**
 * @license GPL-2.0-or-later
 */
class ValueTypeType extends EnumType {

	public function __construct() {
		$config = [
			'values' => [
				'novalue' => [
					'value' => ValueType::TYPE_NO_VALUE,
				],
				'somevalue' => [
					'value' => ValueType::TYPE_SOME_VALUE,
				],
				'value' => [
					'value' => ValueType::TYPE_VALUE,
				],
			],
		];
		parent::__construct( $config );
	}

}
