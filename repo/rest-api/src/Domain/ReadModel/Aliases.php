<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class Aliases extends ArrayObject {

	public function __construct( AliasesInLanguage ...$aliases ) {
		parent::__construct(
			array_combine(
				array_map( fn( AliasesInLanguage $desc ) => $desc->getLanguageCode(), $aliases ),
				$aliases
			)
		);
	}
}
