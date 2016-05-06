<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class SearchFieldDefinitionsBuilder {

	/**
	 * @return ItemFieldDefinitions
	 */
	public function newItemFieldDefinitions( array $languageCodes ) {
		return new ItemFieldDefinitions(
			$this->newLabelsProviderFieldDefinitions( $languageCodes ),
			$this->newDescriptionsProviderFieldDefinitions( $languageCodes )
		);
	}

	/**
	 * @return PropertyFieldDefinitions
	 */
	public function newPropertyFieldDefinitions( array $languageCodes ) {
		return new PropertyFieldDefinitions(
			$this->newLabelsProviderFieldDefinitions( $languageCodes ),
			$this->newDescriptionsProviderFieldDefinitions( $languageCodes )
		);
	}

	private function newLabelsProviderFieldDefinitions( array $languageCodes ) {
		return new LabelsProviderFieldDefinitions(
			new TermSearchFieldDefinition(),
			$languageCodes
		);

	}

	private function newDescriptionsProviderFieldDefinitions( array $languageCodes ) {
		return new DescriptionsProviderFieldDefinitions(
			new TermSearchFieldDefinition(),
			$languageCodes
		);
	}

}
