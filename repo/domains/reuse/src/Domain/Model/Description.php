<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Description {

	public function __construct( public readonly string $languageCode, public readonly string $text ) {
	}
}
