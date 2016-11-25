<?php

namespace Wikibase\Client\Hooks;

use Config;
use Content;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\InterwikiSorter;
use Wikibase\NoLangLinkHandler;
use Wikibase\SettingsArray;

class InterwikiSortingHookHandlers {

	/**
	 * @var InterwikiSorter
	 */
	private $interwikiSorter;

	/**
	 * @var bool
	 */
	private $alwaysSort;

	/**
	 * @return InterwikiSortingConfig
	 */
	public static function newFromGlobalState() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		if ( $config->has( 'InterwikiSortingSort' ) ) {
			return self::newFromInterwikiSortingConfig( $config );
		}

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		return self::newFromWikibaseConfig( $settings );
	}

	/**
	 * @param Config $config
	 *
	 * @return InterwikiSortingHookHandlers
	 */
	public static function newFromInterwikiSortingConfig( Config $config ) {
		$interwikiSorter = new InterwikiSorter(
			$config->get( 'InterwikiSortingSort' ),
			$config->get( 'InterwikiSortingInterwikiSortOrders' ),
			$config->get( 'InterwikiSortingSortPrepend' )
		);

		return new InterwikiSortingHookHandlers(
			$interwikiSorter,
			$config->get( 'InterwikiSortingAlwaysSort' )
		);
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return InterwikiSortingHookHandlers
	 */
	public static function newFromWikibaseConfig( SettingsArray $settings ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$interwikiSorter = new InterwikiSorter(
			$settings->getSetting( 'sort' ),
			$settings->getSetting( 'interwikiSortOrders' ),
			$settings->getSetting( 'sortPrepend' )
		);

		return new InterwikiSortingHookHandlers(
			$interwikiSorter,
			$settings->getSetting( 'alwaysSort' )
		);
	}

	/**
	 * Static handler for the ContentAlterParserOutput hook.
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public static function onContentAlterParserOutput(
		Content $content,
		Title $title,
		ParserOutput $parserOutput
	) {
		$handler = self::newFromGlobalState();
		$handler->doContentAlterParserOutput( $parserOutput );

		return true;
	}

	/**
	 * @param InterwikiSorter $sorter
	 * @param bool $alwaysSort
	 */
	public function __construct( InterwikiSorter $sorter, $alwaysSort ) {
		$this->interwikiSorter = $sorter;
		$this->alwaysSort = $alwaysSort;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function doContentAlterParserOutput( ParserOutput $parserOutput ) {
		if ( $this->alwaysSort || $this->hasNoExternalLangLinks( $parserOutput ) === false ) {
			$interwikiLinks = $parserOutput->getLanguageLinks();
			$sortedLinks = $this->interwikiSorter->sortLinks( $interwikiLinks );
			$parserOutput->setLanguageLinks( $sortedLinks );
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	private function hasNoExternalLangLinks( ParserOutput $parserOutput ) {
		$noExternalLangLinks = NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );

		if ( in_array( '*', $noExternalLangLinks ) ) {
			return true;
		}

		return false;
	}

}
