<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use Wikibase\DataModel\Term\TermTypes;

/**
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
final class TermTypeIds {

	public const LABEL_TYPE_ID = 1;
	public const DESCRIPTION_TYPE_ID = 2;
	public const ALIAS_TYPE_ID = 3;

	public const TYPE_IDS = [
		TermTypes::TYPE_LABEL => self::LABEL_TYPE_ID,
		TermTypes::TYPE_DESCRIPTION => self::DESCRIPTION_TYPE_ID,
		TermTypes::TYPE_ALIAS => self::ALIAS_TYPE_ID,
	];

}
