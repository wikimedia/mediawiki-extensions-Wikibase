<?php

namespace Wikibase\DataModel\Statement;

/**
 * Interface for classes that contain a StatementList.
 *
 * @since 2.2.0
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
interface StatementListProvider {

	/**
	 * @return StatementList
	 */
	public function getStatements();

}
