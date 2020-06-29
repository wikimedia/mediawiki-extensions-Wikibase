<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\ParserLimitReportPrepareHook;
use Parser;
use ParserOutput;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ParserLimitReportPrepareHookHandler implements ParserLimitReportPrepareHook {

	/**
	 * @var RestrictedEntityLookup
	 */
	private $restrictedEntityLookup;

	/**
	 * @var int
	 */
	private $entityAccessLimit;

	/**
	 * @param RestrictedEntityLookup $restrictedEntityLookup
	 * @param int $entityAccessLimit
	 */
	public function __construct( RestrictedEntityLookup $restrictedEntityLookup, $entityAccessLimit ) {
		$this->restrictedEntityLookup = $restrictedEntityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getRestrictedEntityLookup(),
			$wikibaseClient->getSettings()->getSetting( 'entityAccessLimit' )
		);
	}

	/**
	 * @param Parser $parser
	 * @param ParserOutput $output
	 *
	 * @return bool
	 */
	public function onParserLimitReportPrepare( $parser, $output ) {
		$output->setLimitReportData(
			'limitreport-entityaccesscount',
			[
				$this->restrictedEntityLookup->getEntityAccessCount(),
				$this->entityAccessLimit
			]
		);

		return true;
	}

}
