<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 *
 * Definitions for any entity that has descriptions.
 */
class DescriptionsProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->languageCodes = $languageCodes;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		$fields = [];

		$fields['descriptions'] = new DescriptionsField( $this->languageCodes );

		return $fields;
	}

}
