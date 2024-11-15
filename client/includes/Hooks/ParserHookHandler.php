<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\ParserClearStateHook;
use MediaWiki\Hook\ParserLimitReportPrepareHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookupFactory;
use Wikibase\Lib\SettingsArray;

/**
 * Handler for some Parser-related hooks.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ParserHookHandler implements
	ParserClearStateHook,
	ParserLimitReportPrepareHook
{
	private RestrictedEntityLookupFactory $restrictedEntityLookupFactory;

	private int $entityAccessLimit;

	public function __construct(
		RestrictedEntityLookupFactory $restrictedEntityLookupFactory,
		int $entityAccessLimit
	) {
		$this->restrictedEntityLookupFactory = $restrictedEntityLookupFactory;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	public static function factory(
		RestrictedEntityLookupFactory $restrictedEntityLookupFactory,
		SettingsArray $clientSettings
	): self {
		return new self(
			$restrictedEntityLookupFactory,
			$clientSettings->getSetting( 'entityAccessLimit' )
		);
	}

	/**
	 * Called when resetting the state of the Parser between parses.
	 *
	 * @param Parser $parser
	 */
	public function onParserClearState( $parser ) {
		// Reset the entity access limits, per T127462
		$this->restrictedEntityLookupFactory->getRestrictedEntityLookup( $parser )->reset();
	}

	/**
	 * @param Parser $parser
	 * @param ParserOutput $parserOutput
	 */
	public function onParserLimitReportPrepare( $parser, $parserOutput ) {
		$parserOutput->setLimitReportData(
			'limitreport-entityaccesscount',
			[
				$this->restrictedEntityLookupFactory->getRestrictedEntityLookup( $parser )->getEntityAccessCount(),
				$this->entityAccessLimit,
			]
		);
	}

}
