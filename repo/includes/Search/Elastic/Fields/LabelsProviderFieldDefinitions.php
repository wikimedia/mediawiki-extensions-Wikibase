<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\WikibaseIndexField;

/**
 * Definitions for any entity that has labels.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
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
		return [
			LabelCountField::NAME => new LabelCountField(),
			LabelsField::NAME => new LabelsField( $this->languageCodes ),
			AllLabelsField::NAME => new AllLabelsField(),
		];
	}

}
