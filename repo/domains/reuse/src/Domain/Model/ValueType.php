<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 */
enum ValueType: string {
	case TYPE_VALUE = 'value';
	case TYPE_NO_VALUE = 'novalue';
	case TYPE_SOME_VALUE = 'somevalue';

	public static function fromString( string $type ): self {
		return match ( $type ) {
			self::TYPE_VALUE->value => self::TYPE_VALUE,
			self::TYPE_SOME_VALUE->value => self::TYPE_SOME_VALUE,
			self::TYPE_NO_VALUE->value => self::TYPE_NO_VALUE,
			default => throw new InvalidArgumentException(
				'valueType must be one of "value", "somevalue", "novalue"'
			),
		};
	}
}
