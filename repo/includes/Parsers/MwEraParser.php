<?php

namespace Wikibase\Repo\Parsers;

use Language;
use MediaWiki\MediaWikiServices;
use ValueParsers\EraParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * Class to parse localized era from values.
 * @license GPL-2.0-or-later
 */
class MwEraParser extends EraParser {

	public const FORMAT_NAME = 'mw-era';

	public const BCE_MESSAGE_KEY = 'wikibase-time-precision-BCE';
	public const CE_MESSAGE_KEY = 'wikibase-time-precision-CE';

	/**
	 * @var Language
	 */
	private $lang;

	public function __construct( ParserOptions $options ) {
		parent::__construct( $options );

		$this->lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $this->getOption( ValueParser::OPT_LANG ) );
	}

	/**
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return string[] Array of the parsed era constant and the value with the era stripped.
	 */
	protected function stringParse( $value ) {
		$era = $this->parseEra( $value, $this->lang );
		if ( $era === null && $this->lang->getCode() !== 'en' ) {
			$era = $this->parseEra( $value, MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ) );
		}

		if ( $era !== null ) {
			return $era;
		}

		return parent::stringParse( $value );
	}

	/**
	 * Try to parse era from the value in the given language.
	 *
	 * @param string $value
	 * @param Language $language
	 * @return string[]|null
	 */
	private function parseEra( $value, Language $language ) {
		$bceEra = $this->parseEraWithMessage( $value, $language->getMessage( self::BCE_MESSAGE_KEY ) );
		if ( $bceEra !== null ) {
			return [
				self::BEFORE_COMMON_ERA,
				$bceEra,
			];
		}

		$ceEra = $this->parseEraWithMessage( $value, $language->getMessage( self::CE_MESSAGE_KEY ) );
		if ( $ceEra !== null ) {
			return [
				self::COMMON_ERA,
				$ceEra,
			];
		}

		return null;
	}

	/**
	 * Try to parse the era from the value using the given message text.
	 *
	 * @param string $value
	 * @param string $msgText
	 * @return string|null The value with the era stripped (if it can be parsed).
	 */
	private function parseEraWithMessage( string $value, string $msgText ): ?string {
		if ( strpos( $msgText, '$1' ) === false || $msgText === '$1' ) {
			return null;
		}

		$regexp = $this->getRegexpFromMessageText( $msgText );
		if ( preg_match(
			'/^' . $regexp . '$/',
			trim( $value ),
			$matches
		) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Transform the message to a pattern we can match era against.
	 * @param $msgText string
	 * @return string
	 */
	private function getRegexpFromMessageText( $msgText ) {
		return str_replace( '\$1', '(.+?)', preg_quote( $msgText, '/' ) );
	}

}
