<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Serialization;

use ArrayObject;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;

/**
 * @license GPL-2.0-or-later
 */
class AliasesSerializer {

	public function serialize( Aliases $aliases ): ArrayObject {
		$serialization = new ArrayObject();
		foreach ( $aliases as $languageCode => $aliasesInLanguage ) {
			$serialization[$languageCode] = $aliasesInLanguage->getAliases();
		}
		return $serialization;
	}

}
