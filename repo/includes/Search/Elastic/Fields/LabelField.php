<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelField implements SearchIndexField {

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
	public function getMapping() {
		return array(
			'type' => 'nested',
			'properties' => $this->getTermFieldProperties()
		);
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return array
	 */
	public function getFieldData( EntityDocument $entity ) {
		$terms = $entity->getFingerprint();

		return $this->buildTermsData( $terms->getLabels() );
	}

	/**
	 * @return string
	 */
	protected function getPrefix() {
		return 'label';
	}

	/**
	 * @return array
	 */
	protected function getTermFieldProperties() {
		$prefix = $this->getPrefix();
		$fields = array();

		foreach ( $this->languageCodes as $languageCode ) {
			$fields[$prefix . '_' . $languageCode] = array(
				'type' => 'string'
			);
		}

		return $fields;
	}

	/**
	 * @param TermList $terms
	 *
	 * @return array
	 */
	protected function buildTermsData( TermList $terms ) {
		$prefix = $this->getPrefix();
		$termsArray = array();

		foreach ( $terms->toTextArray() as $languageCode => $term ) {
			$termsArray[$prefix . '_' . $languageCode] = $term;
		}

		return $termsArray;
	}

}
