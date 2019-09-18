<?php

namespace Wikibase\Repo\Parsers;

use Language;
use ValueParsers\EraParser;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * Class to parse localized era from values.
 */
class MwEraParser extends EraParser {

	const FORMAT_NAME = 'mw-era';

	const MESSAGE_KEY = 'wikibase-time-precision-BCE';

	/**
	 * @var Language
	 */
	private $lang;

	public function __construct( ParserOptions $options ) {
		parent::__construct( $options );

		$this->lang = Language::factory( $this->getOption( ValueParser::OPT_LANG ) );
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
			$era = $this->parseEra( $value, Language::factory( 'en' ) );
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
		$msgText = $language->getMessage( self::MESSAGE_KEY );
		if ( strpos( $msgText, '$1' ) === false || $msgText === '$1' ) {
			return null;
		}

		$regexp = $this->getRegexpFromMessageText( $msgText );
		if ( preg_match(
			'/^' . $regexp . '$/',
			trim( $value ),
			$matches
		) ) {
			return [
				self::BEFORE_COMMON_ERA,
				$matches[1]
			];
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
