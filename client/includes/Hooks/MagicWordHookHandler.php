<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\GetMagicVariableIDsHook;
use MediaWiki\Hook\ParserGetVariableValueSwitchHook;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderJqueryMsgModuleMagicWordsHook;
use Wikibase\Lib\SettingsArray;

/**
 * File defining hooks related to magic words
 * @license GPL-2.0-or-later
 */
class MagicWordHookHandler implements
	GetMagicVariableIDsHook,
	ParserGetVariableValueSwitchHook,
	ResourceLoaderJqueryMsgModuleMagicWordsHook
{

	/**
	 * @var SettingsArray
	 */
	protected $settings;

	public function __construct( SettingsArray $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register all magic words.
	 *
	 * @param string[] &$variableIds
	 */
	public function onGetMagicVariableIDs( &$variableIds ): void {
		$variableIds[] = 'noexternallanglinks';
		$variableIds[] = 'wbreponame';
	}

	/**
	 * Gets the user-facing repository name.
	 *
	 * This can either be a message's text, or the raw value from
	 * settings if that is not a message
	 *
	 * @param Language|string $lang Language (code) to get text in
	 *
	 * @return string
	 */
	protected function getRepoName( $lang ) {
		$repoSiteName = $this->settings->getSetting( 'repoSiteName' );

		$message = new Message( $repoSiteName );
		$message->inLanguage( $lang );

		if ( $message->exists() ) {
			return $message->text();
		} else {
			return $repoSiteName;
		}
	}

	/**
	 * Handler for the ParserGetVariableValueSwitch hook.
	 * Apply the magic word.
	 *
	 * @param Parser $parser
	 * @param string[] &$cache
	 * @param string $magicWordId
	 * @param ?string &$ret
	 * @param PPFrame $frame
	 */
	public function onParserGetVariableValueSwitch( $parser, &$cache, $magicWordId, &$ret, $frame ) {
		if ( $magicWordId === 'noexternallanglinks' ) {
			NoLangLinkHandler::handle( $parser, '*' );
			$ret = $cache[$magicWordId] = '';
		} elseif ( $magicWordId === 'wbreponame' ) {
			$lang = $parser->getTargetLanguage();
			$ret = $cache[$magicWordId] = $this->getRepoName( $lang );
		}
	}

	/**
	 * Handler for the ResourceLoaderJqueryMsgModuleMagicWords hook.
	 * Adds magic word constant(s) for use by jQueryMsg.
	 *
	 * @param RL\Context $context
	 * @param string[] &$magicWords Associative array mapping all-caps magic
	 *  words to string values
	 */
	public function onResourceLoaderJqueryMsgModuleMagicWords(
		RL\Context $context,
		array &$magicWords
	): void {
		$magicWords['WBREPONAME'] = $this->getRepoName( $context->getLanguage() );
	}

}
