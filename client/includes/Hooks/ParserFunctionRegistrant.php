<?php

namespace Wikibase\Client\Hooks;

use MediaWiki\Hook\ParserFirstCallInitHook;
use Parser;
use PPFrame;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Lib\ParserFunctions\CommaSeparatedList;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class ParserFunctionRegistrant implements ParserFirstCallInitHook {

	/**
	 * @var bool Setting to enable use of property parser function.
	 */
	private $allowDataTransclusion;

	/**
	 * @var bool Setting to enable local override of descriptions.
	 */
	private $allowLocalShortDesc;

	public function __construct( bool $allowDataTransclusion, bool $allowLocalShortDesc ) {
		$this->allowDataTransclusion = $allowDataTransclusion;
		$this->allowLocalShortDesc = $allowLocalShortDesc;
	}

	public static function factory(
		SettingsArray $clientSettings
	): self {
		return new self(
			$clientSettings->getSetting( 'allowDataTransclusion' ),
			$clientSettings->getSetting( 'allowLocalShortDesc' )
		);
	}

	/** @param Parser $parser */
	public function onParserFirstCallInit( $parser ): void {
		$this->registerNoLangLinkHandler( $parser );
		$this->registerShortDescHandler( $parser );
		$this->registerParserFunctions( $parser );
	}

	private function registerNoLangLinkHandler( Parser $parser ) {
		$parser->setFunctionHook(
			'noexternallanglinks',
			[ NoLangLinkHandler::class, 'handle' ],
			Parser::SFH_NO_HASH
		);
	}

	private function registerShortDescHandler( Parser $parser ) {
		if ( $this->allowLocalShortDesc ) {
			$parser->setFunctionHook(
				'shortdesc',
				[ ShortDescHandler::class, 'handle' ],
				Parser::SFH_NO_HASH
			);
		}
	}

	private function registerParserFunctions( Parser $parser ) {
		if ( !$this->allowDataTransclusion ) {
			return;
		}

		$parser->setFunctionHook(
			'property',
			function( Parser $parser, PPFrame $frame, array $args ) {
				return Runner::renderEscapedPlainText( $parser, $frame, $args );
			},
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			'statements',
			function( Parser $parser, PPFrame $frame, array $args ) {
				return Runner::renderRichWikitext( $parser, $frame, $args );
			},
			Parser::SFH_OBJECT_ARGS
		);

		$parser->setFunctionHook(
			CommaSeparatedList::NAME,
			[ CommaSeparatedList::class, 'handle' ]
		);
	}

}
