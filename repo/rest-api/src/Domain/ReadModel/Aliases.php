<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

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

	public static function fromAliasGroupList( AliasGroupList $aliasGroupList ): self {
		return new Aliases(
			...array_map(
				fn ( AliasGroup $a ) => new AliasesInLanguage( $a->getLanguageCode(), $a->getAliases() ),
				array_values( $aliasGroupList->toArray() )
			)
		);
	}
}
