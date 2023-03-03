<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;

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
