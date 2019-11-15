<?php

namespace Wikibase\Lib\ParserFunctions;

/**
 * Class definition for the CommaSeparatedList parser function
 */
class CommaSeparatedList {

	public const NAME = "commaSeparatedList";

	/**
	 * @param \Parser $parser
	 * @param mixed ...$words
	 * @return mixed
	 */
	public static function handle( $parser, ...$words ) {
		return $parser->getTargetLanguage()->commaList( $words );
	}
}
