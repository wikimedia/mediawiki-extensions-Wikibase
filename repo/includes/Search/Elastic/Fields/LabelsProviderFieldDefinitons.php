<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Definitions for any entity that has labels.
 */
class LabelsProviderFieldDefinitions implements FieldDefinitions {

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
	 * @return SearchIndexField[]
	 */
	public function getFields() {

		$fields = $this->getLabelFields();
		$fields['label_count'] = new LabelCountField();

		return $fields;
	}

	/**
	 * @return SearchIndexField[]
	 */
	private function getLabelFields() {
		$fields = [];

		// TODO: next patch will have actual label fields

		return $fields;
	}

}
