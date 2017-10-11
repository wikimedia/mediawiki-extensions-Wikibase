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
	 * @var callable[]
	 */
	private $definitions;

	/**
	 * StatementProviderFieldDefinitions constructor.
	 * @param string[] $properties List of properties to index
	 * @param callable[] $definitions
	 */
	public function __construct( array $properties, array $definitions ) {
		$this->definitions = $definitions;
		$this->properties = $properties;
	}

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields() {
		$fields['statement_keywords'] = new StatementsField( $this->properties, $this->definitions );
		$fields['statement_count'] = new StatementCountField();
		return $fields;
	}

}
