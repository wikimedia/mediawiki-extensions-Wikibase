<?php

namespace Wikibase\Repo\Api;

/**
 * Service configured for feature-based parsing of parameters of wbsetclaim API into request objects.
 *
 * The service is configured with a list (possibly empty) of parsers that are supposed to parse
 * particular classes of requests (e.g. statement change on a particular type of entity). It
 * also has a "base" parser to be used as a fallback when none of configure special-case parsers
 * is applicable for a particular input.
 */
class DispatchingSetClaimRequestParser implements SetClaimRequestParser {

	/**
	 * @var SetClaimRequestParser
	 */
	private $baseParser;

	/**
	 * @var DispatchableSetClaimRequestParser[]
	 */
	private $parsers;

	public function __construct( SetClaimRequestParser $baseParser, array $parsers ) {
		$this->baseParser = $baseParser;
		$this->parsers = $parsers;
	}

	public function parse( array $params ) {
		foreach ( $this->parsers as $parser ) {
			if ( $parser->canParse( $params ) ) {
				return $parser->parse( $params );
			}
		}

		return $this->baseParser->parse( $params );
	}

}
