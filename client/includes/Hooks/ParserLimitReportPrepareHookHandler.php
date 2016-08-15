<?php

namespace Wikibase\Client\Hooks;

use Parser;
use ParserOutput;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @since 0.5.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class ParserLimitReportPrepareHookHandler {

	/**
	 * @var RestrictedEntityLookup
	 */
	private $restrictedEntityLookup;

	/**
	 * @param RestrictedEntityLookup $restrictedEntityLookup
	 */
	public function __construct( RestrictedEntityLookup $restrictedEntityLookup ) {
		$this->restrictedEntityLookup = $restrictedEntityLookup;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getRestrictedEntityLookup()
		);
	}

	/**
	 * @param Parser $parser
	 * @param ParserOutput $output
	 *
	 * @return bool
	 */
	public static function onParserLimitReportPrepare( Parser $parser, ParserOutput $output ) {
		$handler = self::newFromGlobalState();

		return $handler->doParserLimitReportPrepare( $parser, $output );
	}

	/**
	 * @param Parser $parser
	 * @param ParserOutput $output
	 *
	 * @return bool
	 */
	public function doParserLimitReportPrepare( Parser $parser, ParserOutput $output ) {
		$output->setLimitReportData(
			'EntityAccessCount',
			$this->restrictedEntityLookup->getEntityAccessCount()
		);

		return true;
	}

}
