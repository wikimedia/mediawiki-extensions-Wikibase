<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * This is a collection of field definitions.
 * This interface should be implemented by specific definition
 * classes which know which fields they deal with.
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
interface FieldDefinitions {

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields();

}
