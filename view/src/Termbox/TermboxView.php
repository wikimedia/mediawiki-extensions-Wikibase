<?php

namespace Wikibase\View;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class TermboxView implements EntityTermsView {

	public function __construct() {
	}

	public function getHtml(
		$mainLanguageCode,
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups = null,
		EntityId $entityId = null
	) {
		return '';
	}

	public function getTitleHtml( EntityId $entityId = null ) {
		return '';
	}

}
