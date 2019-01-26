<?php
namespace Wikibase\Repo\Search\Fields;

// phpcs:disable Wikibase.Namespaces.FullQualifiedClassName.Found
/**
 * This is a collection of field definitions.
 * This interface should be implemented by specific definition
 * classes which know which fields they deal with.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
interface FieldDefinitions extends \Wikibase\Repo\Search\Elastic\Fields\FieldDefinitions {

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields();

}
