<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
enum ValueType: string {
	case TYPE_VALUE = 'value';
	case TYPE_NO_VALUE = 'novalue';
	case TYPE_SOME_VALUE = 'somevalue';
}
