<?php

namespace Wikibase\Repo\Api;

/**
 * TEST
 */
class DispatchingSetClaimRequestParser implements  SetClaimRequestParser {

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
