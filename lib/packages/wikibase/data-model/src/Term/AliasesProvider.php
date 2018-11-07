<?php

namespace Wikibase\DataModel\Term;

/**
 * Common interface for classes (typically Entities) that contain an AliasGroupList. Implementations
 * must guarantee this returns the original, mutable object by reference.
 *
 * @since 4.1
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface AliasesProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return AliasGroupList
	 */
	public function getAliasGroups();

}
