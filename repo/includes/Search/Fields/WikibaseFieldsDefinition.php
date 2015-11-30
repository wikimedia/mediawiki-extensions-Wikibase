<?php

namespace Wikibase\Repo\Search\Fields;

use Hooks;

class WikibaseFieldsDefinition {

	/**
	 * @return Field[] Array key is field name.
	 */
	public function getFields() {
		$fields = array(
			'sitelink_count' => new SiteLinkCountField(),
			'statement_count' => new StatementCountField()
		);

		Hooks::run( 'WikibaseSearchFields', array( &$fields ) );

		return $fields;
	}

}
