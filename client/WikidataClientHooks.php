<?php
/**
 * Hooks for WikidataClient extension
 * 
 * @file
 * @ingroup Extensions
 */

class WikidataClientHooks {
	protected static $cache = array();

	public static function parserBeforeTidy( &$parser, &$text ) {
		global $wgLanguageCode, $wgWikidataClientNamespaces;

		$title = $parser->getTitle();

		if($parser->getOptions()->getInterfaceMessage() || !in_array( $title->getNamespace(), $wgWikidataClientNamespaces ) ) {
			return true;
		}

		$db_title = $title->getDBkey();
		if(isset(self::$cache[$db_title])) {
			$links = self::$cache[$db_title];
		} else {
			$links = self::getLinks($db_title);

			//If there was an error while getting links, we use the current links
			if($links === false) {
				$links = self::readLinksFromDB( wfGetDB( DB_SLAVE ), $title->getArticleID() );
			}

 			self::$cache[$db_title] = $links;
		}

		//Remove the link to the site language
		unset($links[$wgLanguageCode]);

		//If a link exists in wikitext, override wikidata link to the same language
		//TODO: ability to remove a link without replacing it
		$out = $parser->getOutput();
		foreach($out->mLanguageLinks as $v) {
			unset($links[self::getCodeFromLink($v)]);
		}

		//Pack the links properly
		foreach($links as $k => $v) {
			//TODO: use a function?
			$out->mLanguageLinks[] = "$k:$v";
		}

		//Sort the links
		self::sortLinks( $out->mLanguageLinks );

		return true;
	}

	/**
	 * Get language code from a link in ParserOutput::mLanguageLinks
	 */
	public static function getCodeFromLink($link) {
		return substr($link, 0, strpos($link, ':'));
	}

	/**
	 * Get the list of links for a title.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	public static function getLinks( $db_title ) {
		$dir = dirname(__FILE__) . '/';
		$file = "$dir/test/$db_title.json";
		if(file_exists($file)) {
			return get_object_vars(json_decode(file_get_contents($file)));
		} else {
			return false;
		}
	}

	/**
	 * Read interlanguage links from a database, and return them in the same format as getLinks()
	 *
	 * @param	$dbr - Database.
	 * @param	$articleid - ID of the article whose links should be returned.
	 * @returns	The array with the links. If there are no links, an empty array is returned.
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	static function readLinksFromDB( $dbr, $articleid ) {
		$res = $dbr->select(
			array( 'langlinks' ),
			array( 'll_lang', 'll_title' ),
			array( 'll_from' => $articleid ),
			__FUNCTION__
		);
		$a = array();
		foreach( $res as $row ) {
			$a[$row->ll_lang] = $row->ll_title;
		}
		return $a;
	}

	/**
	 * Sort an array of links in-place
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	static function sortLinks( &$a ) {
		global $wgWikidataClientSort;
		switch( $wgWikidataClientSort ) {
			case 'code':
				usort($a, 'WikidataClientHooks::compareCode');
				break;
			case 'alphabetic':
				usort($a, 'WikidataClientHooks::compareAlphabetic');
				break;
			case 'alphabetic_revised':
				usort($a, 'WikidataClientHooks::compareAlphabeticRevised');
				break;
		}
	}

	/**
	 * Compare two interlanguage links by order of alphabet, based on language code.
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	static function compareCode($a, $b) {
		return strcmp(self::getCodeFromLink($a), self::getCodeFromLink($b));
	}

	/**
	 * Compare two interlanguage links by order of alphabet, based on local language.
	 *
	 * List from http://meta.wikimedia.org/w/index.php?title=Interwiki_sorting_order&oldid=2022604#By_order_of_alphabet.2C_based_on_local_language
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	static function compareAlphabetic($a, $b) {
		global $wgWikidataClientSortPrepend;
		static $order = array(
			'ace', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
			'roa-rup', 'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'bm', 'bn',
			'zh-min-nan', 'nan', 'map-bms', 'ba', 'be', 'be-x-old', 'bh', 'bcl',
			'bi', 'bar', 'bo', 'bs', 'br', 'bg', 'bxr', 'ca', 'cv', 'ceb', 'cs',
			'ch', 'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk',
			'pdc', 'de', 'dv', 'nv', 'dsb', 'dz', 'mh', 'et', 'el', 'eml', 'en',
			'myv', 'es', 'eo', 'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy',
			'ff', 'fur', 'ga', 'gv', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu',
			'got', 'hak', 'xal', 'ko', 'ha', 'haw', 'hy', 'hi', 'ho', 'hsb',
			'hr', 'io', 'ig', 'ilo', 'bpy', 'id', 'ia', 'ie', 'iu', 'ik', 'os',
			'xh', 'zu', 'is', 'it', 'he', 'jv', 'kl', 'kn', 'kr', 'pam', 'krc',
			'ka', 'ks', 'csb', 'kk', 'kw', 'rw', 'ky', 'rn', 'sw', 'kv', 'kg',
			'ht', 'ku', 'kj', 'lad', 'lbe', 'lo', 'la', 'lv', 'lb', 'lt', 'lij',
			'li', 'ln', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'ml', 'mt', 'mi',
			'mr', 'arz', 'mzn', 'ms', 'cdo', 'mwl', 'mdf', 'mo', 'mn', 'mus',
			'my', 'nah', 'na', 'fj', 'nl', 'nds-nl', 'cr', 'ne', 'new', 'ja',
			'nap', 'ce', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov', 'ii', 'oc',
			'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pag', 'pnb',
			'pap', 'ps', 'km', 'pcd', 'pms', 'tpi', 'nds', 'pl', 'tokipona',
			'tp', 'pnt', 'pt', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy',
			'rm', 'qu', 'ru', 'sah', 'se', 'sm', 'sa', 'sg', 'sc', 'sco', 'stq',
			'st', 'tn', 'sq', 'scn', 'si', 'simple', 'sd', 'ss', 'sk', 'cu',
			'sl', 'szl', 'so', 'ckb', 'srn', 'sr', 'sh', 'su', 'fi', 'sv', 'tl',
			'ta', 'kab', 'roa-tara', 'tt', 'te', 'tet', 'th', 'ti', 'tg', 'to',
			'chr', 'chy', 've', 'tr', 'tk', 'tw', 'udm', 'bug', 'uk', 'ur',
			'ug', 'za', 'vec', 'vi', 'vo', 'fiu-vro', 'wa', 'zh-classical',
			'vls', 'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea',
			'bat-smg', 'zh', 'zh-tw', 'zh-cn',
		);
		static $orderMerged = false;

		$a = self::getCodeFromLink($a);
		$b = self::getCodeFromLink($b);

		if($a == $b) return 0;

		if(!$orderMerged && isset($wgWikidataClientSortPrepend) && is_array($wgWikidataClientSortPrepend)) {
			$order = array_merge($wgWikidataClientSortPrepend, $order);
		}
		$orderMerged = true;

		$a=array_search($a, $order);
		$b=array_search($b, $order);

		return ($a>$b)?1:(($a<$b)?-1:0);
	}

	/**
	 * Compare two interlanguage links by order of alphabet, based on local language (by first
	 * word).
	 *
	 * List from http://meta.wikimedia.org/w/index.php?title=Interwiki_sorting_order&oldid=2022604#By_order_of_alphabet.2C_based_on_local_language_.28by_first_word.29
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	static function compareAlphabeticRevised($a, $b) {
		global $wgWikidataClientSortPrepend;
		static $order = array(
			'ace', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc',
			'roa-rup', 'frp', 'as', 'ast', 'gn', 'av', 'ay', 'az', 'id', 'ms',
			'bm', 'bn', 'zh-min-nan', 'nan', 'map-bms', 'jv', 'su', 'ba', 'be',
			'be-x-old', 'bh', 'bcl', 'bi', 'bar', 'bo', 'bs', 'br', 'bug', 'bg',
			'bxr', 'ca', 'ceb', 'cv', 'cs', 'ch', 'cbk-zam', 'ny', 'sn', 'tum',
			'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de', 'dv', 'nv', 'dsb', 'na',
			'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo', 'ext', 'eu',
			'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm',
			'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got', 'hak', 'xal', 'ko',
			'ha', 'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo',
			'bpy', 'ia', 'ie', 'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he',
			'kl', 'kn', 'kr', 'pam', 'ka', 'ks', 'csb', 'kk', 'kw', 'rw', 'ky',
			'rn', 'sw', 'kv', 'kg', 'ht', 'ku', 'kj', 'lad', 'lbe', 'lo', 'la',
			'lv', 'to', 'lb', 'lt', 'lij', 'li', 'ln', 'jbo', 'lg', 'lmo', 'hu',
			'mk', 'mg', 'ml', 'krc', 'mt', 'mi', 'mr', 'arz', 'mzn', 'cdo',
			'mwl', 'mdf', 'mo', 'mn', 'mus', 'my', 'nah', 'fj', 'nl', 'nds-nl',
			'cr', 'ne', 'new', 'ja', 'nap', 'ce', 'pih', 'no', 'nb', 'nn',
			'nrm', 'nov', 'ii', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa',
			'pi', 'pag', 'pnb', 'pap', 'ps', 'km', 'pcd', 'pms', 'nds', 'pl',
			'pnt', 'pt', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm',
			'qu', 'ru', 'sah', 'se', 'sa', 'sg', 'sc', 'sco', 'stq', 'st', 'tn',
			'sq', 'scn', 'si', 'simple', 'sd', 'ss', 'sk', 'sl', 'cu', 'szl',
			'so', 'ckb', 'srn', 'sr', 'sh', 'fi', 'sv', 'tl', 'ta', 'kab',
			'roa-tara', 'tt', 'te', 'tet', 'th', 'vi', 'ti', 'tg', 'tpi',
			'tokipona', 'tp', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'udm', 'uk',
			'ur', 'ug', 'za', 'vec', 'vo', 'fiu-vro', 'wa', 'zh-classical',
			'vls', 'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea',
			'bat-smg', 'zh', 'zh-tw', 'zh-cn',
		);
		static $orderMerged = false;

		$a = self::getCodeFromLink($a);
		$b = self::getCodeFromLink($b);

		if($a == $b) return 0;

		if(!$orderMerged && isset($wgWikidataClientSortPrepend) && is_array($wgWikidataClientSortPrepend)) {
			$order = array_merge($wgWikidataClientSortPrepend, $order);
		}
		$orderMerged = true;

		$a=array_search($a, $order);
		$b=array_search($b, $order);

		return ($a>$b)?1:(($a<$b)?-1:0);
	}

}
