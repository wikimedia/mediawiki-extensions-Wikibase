<?php

namespace Wikibase\Client\Hooks;

use Parser;
use Wikibase\DataAccess\PropertyParserFunctionHandler;

class ParserFunctionRegistrant {

	/**
	 * @var PropertyParserFunctionHandler
	 */
	private $propertyParserFunctionHandler;

	/**
	 * @param boolean - setting to enable use of property parser function
	 */
	private $allowDataTransclusion;

	/**
	 * @param PropertyParserFunctionHandler $propertyParserFunctionHandler
	 * @param boolean $allowDataTransclusion
	 */
	public function __construct(
		PropertyParserFunctionHandler $propertyParserFunctionHandler,
		$allowDataTransclusion
	) {
		$this->propertyParserFunctionHandler = $propertyParserFunctionHandler;
		$this->allowDataTransclusion = $allowDataTransclusion;
	}

	/**
	 * @param Parser $parser
	 */
	public function register( Parser $parser ) {
		$this->registerNoLangLinkHandler( $parser );
		$this->registerPropertyParserFunction( $parser );
	}

	/**
	 * @param Parser $parser
	 */
	private function registerNoLangLinkHandler( Parser $parser ) {
		$parser->setFunctionHook(
			'noexternallanglinks',
			'\Wikibase\NoLangLinkHandler::handle',
			SFH_NO_HASH
		);
	}

	/**
	 * @param Parser $parser
	 */
	private function registerPropertyParserFunction( Parser $parser ) {
		if ( !$this->allowDataTransclusion ) {
			return;
		}

		$propertyParserFunctionHandler = $this->propertyParserFunctionHandler;

		$parser->setFunctionHook(
			'property',
			function( Parser $parser, $propertyLabel ) use( $propertyParserFunctionHandler ) {
				return $propertyParserFunctionHandler->handle( $parser, $propertyLabel );
			}
		);

	}
}
