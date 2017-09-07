<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

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
	 * StatementProviderFieldDefinitions constructor.
	 * @param string[] $properties List of properties to index
	 */
	public function __construct( $properties ) {
		$this->properties = $properties;
	}

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields() {
		$fields['statements'] = new StatementsField( $this->properties );
		$fields['statement_count'] = new StatementCountField();
		return $fields;
	}
}
