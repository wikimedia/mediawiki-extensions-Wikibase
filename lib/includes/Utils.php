<?php

namespace Wikibase;

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
 *
 * @file WikibaseUtils.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class Utils {

	/**
	 * Returns a list of language codes that Wikibase supports,
	 * ie the languages that a label or description can be in.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( is_null( $languageCodes ) ) {
			$languageCodes = array_keys( \Language::fetchLanguageNames() );
		}

		return $languageCodes;
	}

	/**
	 * Temporary helper function.
	 * Inserts some sites into the sites table.
	 *
	 * @since 0.1
	 */
	public static function insertTemporarySites() {
		$sitesTable = \Wikibase\SitesTable::singleton();

		$sitesTable->newFromArray( array(
			'global_key' => 'enwiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://en.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'en',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();

		$sitesTable->newFromArray( array(
			'global_key' => 'dewiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://de.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'de',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();

		$sitesTable->newFromArray( array(
			'global_key' => 'nlwiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://nl.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'nl',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();

		$sitesTable->newFromArray( array(
			'global_key' => 'svwiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://sv.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'sv',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();

		$sitesTable->newFromArray( array(
			'global_key' => 'nnwiki',
			'type' => 0,
			'group' => 0,
			'url' => 'https://nn.wikipedia.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'nn',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();

		$sitesTable->newFromArray( array(
			'global_key' => 'enwiktionary',
			'type' => 0,
			'group' => 1,
			'url' => 'https://en.wiktionary.org',
			'page_path' => '/wiki/$1',
			'file_path' => '/w/',
			'local_key' => 'enwiktionary',
			'link_inline' => true,
			'link_navigation' => true,
			'forward' => true,
			'allow_transclusion' => false,
		) )->save();
	}

}
