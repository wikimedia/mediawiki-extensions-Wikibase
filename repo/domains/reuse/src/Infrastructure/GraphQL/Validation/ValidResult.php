<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\GraphQL\Validation;

use GraphQL\Language\AST\DocumentNode;

/**
 * @license GPL-2.0-or-later
 */
class ValidResult {

	public function __construct(
		public readonly DocumentNode $documentNode,
	) {
	}

}
