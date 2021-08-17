<?php

namespace Wikibase\DataModel\Statement;

/**
 * Common interface for classes (typically Entities) that contain a StatementList. Implementations
 * must guarantee this returns the original, mutable object by reference.
 *
 * @since 2.2.0
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
interface StatementListProvider {

	/**
	 * This is guaranteed to return the original, mutable object by reference.
	 *
	 * @return StatementList
	 */
	public function getStatements();

}
