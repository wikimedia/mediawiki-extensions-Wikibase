<?php

namespace Wikibase\DataModel\Term;

/**
 * @since 4.0
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface AliasesProvider {

	/**
	 * @return AliasGroupList
	 */
	public function getAliases();

}
