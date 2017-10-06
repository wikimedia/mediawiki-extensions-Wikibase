<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
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
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		return [
			'descriptions' => new DescriptionsField( $this->languageCodes ),
		];
	}

}
