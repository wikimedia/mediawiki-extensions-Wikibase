<?php

namespace Wikibase\Lib\ParserFunctions;

use Parser;

/**
 * Class definition for the CommaSeparatedList parser function
 * @license GPL-2.0-or-later
 */
class CommaSeparatedList {

	public const NAME = "commaSeparatedList";

	public static function handle( Parser $parser, string ...$words ): string {
		return $parser->getTargetLanguage()->commaList( $words );
	}
}
