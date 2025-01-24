<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class PartiallyValidatedDescriptions extends TermList {
	public function asPlainTermList(): TermList {
		return new TermList( $this );
	}
}
