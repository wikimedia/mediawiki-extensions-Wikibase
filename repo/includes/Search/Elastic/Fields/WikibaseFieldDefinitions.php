<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

class WikibaseFieldDefinitions {

	/**
	 * @return SearchIndexField[] Array key is field name.
	 */
	public function getFields() {
		$fields = array(
			'sitelink_count' => new SiteLinkCountField(),
			'statement_count' => new StatementCountField()
		);

		return $fields;
	}

}
