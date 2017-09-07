<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\Lib\DataTypeDefinitions;

/**
 * Fields for an object that has statements.
 */
class StatementProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * List of properties to index.
	 * @var string[]
	 */
	private $properties;

	/**
	 * @var DataTypeDefinitions
	 */
	private $definitions;

	/**
	 * StatementProviderFieldDefinitions constructor.
	 * @param string[] $properties List of properties to index
	 * @param DataTypeDefinitions $definitions
	 */
	public function __construct( $properties, DataTypeDefinitions $definitions ) {
		$this->properties = $properties;
	}

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields() {
		$fields['statements'] = new StatementsField( $this->properties, $this->definitions );
		$fields['statement_count'] = new StatementCountField();
		return $fields;
	}
}
