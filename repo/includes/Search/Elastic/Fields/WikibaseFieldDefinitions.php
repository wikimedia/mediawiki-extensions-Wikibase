<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFieldDefinitions {

	/**
	 * @return SearchIndexField[] Array key is field name.
	 */
	public function getFields() {
		$fields = array(
			'label_count' => new LabelCountField(),
			'sitelink_count' => new SiteLinkCountField(),
			'statement_count' => new StatementCountField()
		);

		return $fields;
	}

}
