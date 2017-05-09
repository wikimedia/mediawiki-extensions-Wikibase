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
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		$fields['label_count'] = new LabelCountField();
		$fields['labels'] = new LabelsField( $this->languageCodes );
		$fields['labels_all'] = new AllLabelsField();

		return $fields;
	}

}
