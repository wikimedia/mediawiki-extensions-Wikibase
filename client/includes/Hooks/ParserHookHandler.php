<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\ParserClearStateHook;
use MediaWiki\Hook\ParserLimitReportPrepareHook;
use Parser;
use ParserOutput;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
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

	/** @var RestrictedEntityLookup */
	private $restrictedEntityLookup;

	/** @var int */
	private $entityAccessLimit;

	public function __construct(
		RestrictedEntityLookup $restrictedEntityLookup,
		int $entityAccessLimit
	) {
		$this->restrictedEntityLookup = $restrictedEntityLookup;
		$this->entityAccessLimit = $entityAccessLimit;
	}

	public static function factory(
		RestrictedEntityLookup $restrictedEntityLookup,
		SettingsArray $clientSettings
	): self {
		return new self(
			$restrictedEntityLookup,
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
		$this->restrictedEntityLookup->reset();
	}

	/**
	 * @param Parser $parser
	 * @param ParserOutput $parserOutput
	 */
	public function onParserLimitReportPrepare( $parser, $parserOutput ) {
		$parserOutput->setLimitReportData(
			'limitreport-entityaccesscount',
			[
				$this->restrictedEntityLookup->getEntityAccessCount(),
				$this->entityAccessLimit,
			]
		);
	}

}
