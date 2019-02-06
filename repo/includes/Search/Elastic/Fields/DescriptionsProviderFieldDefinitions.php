<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\WikibaseIndexField;

/**
 * Definitions for any entity that has descriptions.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class DescriptionsProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * @var string[]
	 */
	private $languageCodes;
	/**
	 * @var array
	 */
	private $searchSettings;

	/**
	 * @param string[] $languageCodes
	 * @param array $searchSettings
	 */
	public function __construct( array $languageCodes, array $searchSettings ) {
		$this->languageCodes = $languageCodes;
		$this->searchSettings = $searchSettings;
	}

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		return [
			DescriptionsField::NAME => new DescriptionsField( $this->languageCodes, $this->searchSettings ),
		];
	}

}
