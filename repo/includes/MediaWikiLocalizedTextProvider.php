<?php

namespace Wikibase\Repo;

use Language;
use Message;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\RawMessageParameter;

/**
 * A LocalizedTextProvider wrapping MediaWiki's message system
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiLocalizedTextProvider implements LocalizedTextProvider {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param Language $language
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * @param string $key
	 * @param string[] $params Parameters that could be used for generating the text
	 *
	 * @return string The localized text
	 */
	public function get( $key, array $params = [] ) {
		return ( new Message( $key, $params, $this->language ) )->text();
	}

	/**
	 * @param string $key
	 * @param array<string|RawMessageParameter> $params Parameters that could be used for generating the text
	 *
	 * @return string The localized text
	 */
	public function getEscaped( $key, array $params = [] ) {
		return ( new Message(
			$key,
			array_map( function ( $param ) {
				return $param instanceof RawMessageParameter ? Message::rawParam( $param->getContents() ) : $param;
			}, $params ),
			$this->language
		) )->escaped();
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return wfMessage( $key )->exists();
	}

	/**
	 * @param string $key Currently ignored
	 *
	 * @return string The language code of the text returned for a specific key.
	 */
	public function getLanguageOf( $key ) {
		return $this->language->getCode();
	}

}
