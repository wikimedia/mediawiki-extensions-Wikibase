<?php

namespace Wikibase\Client\Hooks;

use Html;
use Language;
use Parser;
use ParserOutput;
use StubObject;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;

/**
 * @since 0.5.
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class ParserLimitHookHandlers {

	/**
	 * @var RestrictedEntityLookup
	 */
	private $restrictedEntityLookup;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param RestrictedEntityLookup $restrictedEntityLookup
	 * @param Language $language
	 */
	public function __construct( RestrictedEntityLookup $restrictedEntityLookup, Language $language ) {
		$this->restrictedEntityLookup = $restrictedEntityLookup;
		$this->language = $language;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang;

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		StubObject::unstub( $wgLang );

		return new self(
			$wikibaseClient->getRestrictedEntityLookup(),
			$wgLang
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
	 * @param string $key
	 * @param string &$value
	 * @param string &$report
	 * @param bool $isHTML
	 * @param bool $localize
	 *
	 * @return bool
	 */
	public static function onParserLimitReportFormat( $key, &$value, &$report, $isHTML, $localize ) {
		$handler = self::newFromGlobalState();

		return $handler->doParserLimitReportFormat( $key, $value, $report, $isHTML, $localize );
	}

	/**
	 * @param string $key
	 * @param string &$value
	 * @param string &$report
	 * @param bool $isHTML
	 * @param bool $localize
	 *
	 * @return bool
	 */
	public function doParserLimitReportFormat( $key, &$value, &$report, $isHTML, $localize ) {
		if ( $key !== 'EntityAccessCount' ) {
			return true;
		}

		$language = $localize ? $this->language : Language::factory( 'en' );
		$label = wfMessage( 'wikibase-limitreport-entities-accessed' )->inLanguage( $language )->text();

		if ( $isHTML ) {
			$report .= Html::openElement( 'tr' ) .
			Html::element( 'th', [], $label ) .
			Html::element( 'td', [], $value ) .
			Html::closeElement( 'tr' );
		} else {
			$report .= $label . wfMessage( 'colon-separator' )->inLanguage( $language )->text() . $value;
		}

		return true;
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
