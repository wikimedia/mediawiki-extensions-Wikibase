<?php

namespace Wikibase\Client\Hooks;

use Language;
use Message;
use Parser;
use ResourceLoaderContext;
use Wikibase\SettingsArray;
use Wikibase\Client\WikibaseClient;

/**
 * File defining hooks related to magic words
 * @license GPL-2.0-or-later
 */
class MagicWordHookHandlers {

	/**
	 * @var SettingsArray
	 */
	protected $settings;

	public function __construct( SettingsArray $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$client = WikibaseClient::getDefaultInstance();
		return new self( $client->getSettings() );
	}

	/**
	 * Register all magic words.
	 *
	 * @param string[] &$aCustomVariableIds
	 *
	 * @return bool
	 */
	public static function onMagicWordwgVariableIDs( &$aCustomVariableIds ) {
		$aCustomVariableIds[] = 'noexternallanglinks';
		$aCustomVariableIds[] = 'wbreponame';

		return true;
	}

	/**
	 * Gets the user-facing repository name.
	 *
	 * This can either be a message's text, or the raw value from
	 * settings if that is not a message
	 *
	 * @param Language $lang Language to get text in
	 *
	 * @return string
	 */
	protected function getRepoName( Language $lang ) {
		$repoSiteName = $this->settings->getSetting( 'repoSiteName' );

		$message = new Message( $repoSiteName );
		$message->inLanguage( $lang );

		if ( $message->exists() ) {
			return $message->parse();
		} else {
			return $repoSiteName;
		}
	}

	/**
	 * Static handler for the ParserGetVariableValueSwitch hook
	 *
	 * @param Parser $parser
	 * @param array $cache
	 * @param string $magicWordId
	 * @param string &$ret
	 *
	 * @return bool
	 */
	public static function onParserGetVariableValueSwitch( Parser $parser, $cache, $magicWordId, &$ret ) {
		$handler = self::newFromGlobalState();
		$handler->doParserGetVariableValueSwitch( $parser, $cache, $magicWordId, $ret );
	}

	/**
	 * Apply the magic word.
	 *
	 * @param Parser $parser
	 * @param array $cache
	 * @param string $magicWordId
	 * @param string &$ret
	 *
	 * @return bool
	 */
	protected function doParserGetVariableValueSwitch( Parser $parser, $cache, $magicWordId, &$ret ) {
		if ( $magicWordId === 'noexternallanglinks' ) {
			NoLangLinkHandler::handle( $parser, '*' );
		} elseif ( $magicWordId === 'wbreponame' ) {
			$lang = $parser->getTargetLanguage();
			$ret = $this->getRepoName( $lang );
		}

		return true;
	}

	/**
	 * Static handler for the ResourceLoaderJqueryMsgModuleMagicWords hook
	 *
	 * @param ResourceLoaderContext $context
	 * @param string[] &$magicWords
	 */
	public static function onResourceLoaderJqueryMsgModuleMagicWords( ResourceLoaderContext $context, array &$magicWords ) {
		$handler = self::newFromGlobalState();
		$handler->doResourceLoaderJqueryMsgModuleMagicWords( $context, $magicWords );
	}

	/**
	 * Adds magic word constant(s) for use by jQueryMsg
	 *
	 * @param ResourceLoaderContext $context
	 * @param string[] &$magicWords Associative array mapping all-caps magic
	 *  words to string values
	 */
	protected function doResourceLoaderJqueryMsgModuleMagicWords( ResourceLoaderContext $context, array &$magicWords ) {
		$lang = Language::factory( $context->getLanguage() );
		$magicWords['WBREPONAME'] = $this->getRepoName( $lang );
	}

}
