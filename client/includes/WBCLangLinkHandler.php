<?php
/**
 * Handles language links.
 * TODO: do we really want to refresh this on re-render? push updates from the repo seem to make more sense
 *
 * @since 0.1
 *
 * @file WBCLangLinkHandler.php
 * @ingroup WikibaseClient
 *
 * @licence	GNU GPL v2+
 * @author	Nikola Smolenski <smolensk@eunet.rs>
 */

class WBCLangLinkHandler {
        protected static $sort_order = false;
        protected static $langlinksset = false;

	# todo: this is hackish, is this the best hook to use?
        public static function onParserBeforeTidy( Parser &$parser, &$text ) {
		if ( ! self::$langlinksset ) {
                	if ( self::addLangLinks( $parser, $text ) ) {
                        	self::$langlinksset = true;
                	}
		}
                return true;
        }

        protected static function addLangLinks( Parser &$parser, &$text ) {
                global $wgLanguageCode;

                // If this is an interface message, we don't do anything.
                if( $parser->getOptions()->getInterfaceMessage() ) {
                        return true;
                }

		// If we don't support the namespace, we maybe sort the links, but don't do anything else.
		$title = $parser->getTitle();
		if( !in_array( $title->getNamespace(), \Wikibase\Settings::get( 'namespaces' ) ) ) {
			self::maybeSortLinks( $parser->getOutput()->getLanguageLinks() );
			return true;
		}

		// If all the languages are suppressed, we do the same.
		$out = $parser->getOutput();
		$nei = self::getNoExternalInterlang( $out );
		if( array_key_exists( '*', $nei ) ) {
			self::maybeSortLinks( $out->getLanguageLinks() );
			return true;
		}

		// Here we finally get the links...
		// NOTE: Instead of getFullText(), we need to get a normalized title, and the server should use a locale-aware normalization function yet to be written which has the same output
		$title_text = $title->getFullText();
		
		$links = self::getLinks( $title_text );

		// Always remove the link to the site language.
		// TODO: Commons/Wikispecies should be handled here.
		unset( $links[$wgLanguageCode] );

		if ( is_array( $links ) && is_array( $nei ) ) {
			// Remove the links specified by noexternalinterlang parser function.
			$links = array_diff_key( $links, $nei );

			// Pack the links properly into mLanguageLinks.
			$old_links = $out->getLanguageLinks();
			foreach( $links as $link ) {
				$new_link = $link['site'] . ':' . $link['title'];
				if( !in_array( $new_link, $old_links ) ) {
					$out->addLanguageLink( $new_link );
				}
			}

			// Sort the links, always.
			self::sortLinks( $out->getLanguageLinks() );
		}

		return true;
	}

	public static function resetLangLinks() {
		self::$langlinksset = false;
	}

	/**
	 * Get no_external_interlang parser property.
	 *
	 * @return Array Empty array if not set.
	 */
	public static function getNoExternalInterlang( ParserOutput $out ) {
		$nei = $out->getProperty( 'no_external_interlang' );

		if( empty( $nei ) ) {
			$nei = array();
		} else {
			$nei = unserialize( $nei );
		}

		return $nei;
	}

	/**
	 * Get language code from a link in ParserOutput::mLanguageLinks
	 */
	protected static function getCodeFromLink( $link ) {
		return substr( $link, 0, strpos( $link, ':' ) );
	}

	/**
	 * Get the list of links for a title.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinks( $title_text ) {
		$source = \Wikibase\Settings::get( 'source' );
		if( isset( $source['api'] ) ) {
			return self::getLinksFromApi( $title_text, $source['api'] );
		} elseif( isset( $source['var'] ) ) {
			return self::getLinksFromVar( $title_text, $source['var'] );
		} elseif( isset( $source['dir'] ) ) {
			return self::getLinksFromFile( $title_text, $source['dir'] );
		} else {
			return array();
		}
	}

	/**
	 * Get the list of links for a title from an API.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromApi( $title_text, $api ) {
		global $wgLanguageCode;
		//TODO: Read from configuration.
		$siteSuffix = "wiki";

		$url = $api .
			"?action=wbgetitems&format=php&sites=" . $wgLanguageCode . $siteSuffix . "&titles=" . urlencode( $title_text );
		$api_response = Http::get( $url );
		$api_response = unserialize( $api_response );

		if( !is_array( $api_response ) || isset( $api_response['error'] ) ) {
			return false;
		}

		// Repack the links
		$item = reset( $api_response['items'] );
		$sitelinks = $item['sitelinks'];

		$links = array();
		foreach( $sitelinks as $sitelink ) {
			$site = preg_replace( "/$siteSuffix$/", "", $sitelink['site'] );
			$links[$site] = array( 'site' => $site, 'title' => $sitelink['title'] );
		}

		return $links;
	}

	/**
	 * Get the list of links for a title from a variable. This would generally be used for testing.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromVar( $title_text, $var ) {
		return isset( $var[$title_text] )? $var[$title_text]: false;
	}

	/**
	 * Get the list of links for a title from a file. This would generally be used for testing.
	 * @return Array of links, empty array for no links, false for failure.
	 */
	protected static function getLinksFromFile( $title_text, $dir ) {
		$file = "$dir/$title_text.json";
		if( file_exists( $file ) ) {
			return get_object_vars( json_decode( file_get_contents( $file ) ) );
		} else {
			return false;
		}
	}

	/**
	 * Read interlanguage links from a database, and return them in the same format as getLinks()
	 *
	 * @param	$dbr DatabaseBase
	 * @param	$articleid int ID of the article whose links should be returned.
	 * @return	array The array with the links. If there are no links, an empty array is returned.
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	protected static function readLinksFromDB( $dbr, $articleid ) {
		$res = $dbr->select(
			array( 'langlinks' ),
			array( 'll_lang', 'll_title' ),
			array( 'll_from' => $articleid ),
			__METHOD__
		);
		$a = array();
		foreach( $res as $row ) {
			$a[$row->ll_lang] = $row->ll_title;
		}
		return $a;
	}

	/**
	 * Sort an array of links in-place iff alwaysSort option is turned on.
	 */
	protected static function maybeSortLinks( &$a ) {
		if( \Wikibase\Settings::get( 'alwaysSort' ) ) {
			self::sortLinks( $a );
		}
	}

	/**
	 * Sort an array of links in-place
	 * @version	Copied from InterlanguageExtension rev 114818
	 */
	public static function sortLinks( &$a ) {
		wfProfileIn( __METHOD__ );

		// Prepare the sorting array.
		if( self::$sort_order === false ) {
			if( !self::buildSortOrder() ) {
				// If we encounter an unknown sort setting, just do nothing, for we are kind and generous.
				wfProfileOut( __METHOD__ );
				return;
			}
		}

		// Prepare the array for sorting.
		foreach( $a as $k => $langlink ) {
			$a[$k] = explode( ':', $langlink, 2 );
		}

		usort( $a, 'WBCLangLinkHandler::compareLinks' );

		// Restore the sorted array.
		foreach( $a as $k => $langlink ) {
			$a[$k] = implode( ':', $langlink );
		}
		wfProfileOut( __METHOD__ );
	}

	/**
	 * usort() callback function, compares the links on the basis of self::$sort_order
	 */
	protected static function compareLinks( $a, $b ) {
		$a = $a[0];
		$b = $b[0];

		if( $a == $b ) return 0;

		// If we encounter an unknown language, which may happen if the sort table is not updated, we move it to the bottom.
		$a = array_key_exists( $a, self::$sort_order )? self::$sort_order[$a]: 999999;
		$b = array_key_exists( $b, self::$sort_order )? self::$sort_order[$b]: 999999;

		return ( $a > $b )? 1: ( ( $a < $b )? -1: 0 );
	}

	/**
	 * Build sort order to be used by compareLinks().
	 *
	 * @return bool True if the build was successful, false if not ('none' or
	 * 	unknown sort order).
	 * @version $order_alphabetic from http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename&oldid=3398113
	 * 	$order_alphabetic_revised from http://meta.wikimedia.org/w/index.php?title=MediaWiki:Interwiki_config-sorting_order-native-languagename-firstword&oldid=3395404
	 */
	public static function buildSortOrder() {
		static $order_alphabetic = array(
			'ace', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc', 'roa-rup', 'frp', 'as', 'ast', 'gn',
			'av', 'ay', 'az', 'bm', 'bn', 'bjn', 'zh-min-nan', 'nan', 'map-bms', 'ba', 'be', 'be-x-old', 'bh', 'bcl',
			'bi', 'bg', 'bar', 'bo', 'bs', 'br', 'bxr', 'ca', 'cv', 'ceb', 'cs', 'ch', 'cbk-zam', 'ny', 'sn', 'tum',
			'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de', 'dv', 'nv', 'dsb', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv',
			'es', 'eo', 'ext', 'eu', 'ee', 'fa', 'hif', 'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'gag', 'gd', 'gl',
			'gan', 'ki', 'glk', 'gu', 'got', 'hak', 'xal', 'ko', 'ha', 'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io',
			'ig', 'ilo', 'bpy', 'id', 'ia', 'ie', 'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he', 'jv', 'kl', 'kn',
			'kr', 'pam', 'krc', 'ka', 'ks', 'csb', 'kk', 'kw', 'rw', 'rn', 'sw', 'kv', 'kg', 'ht', 'ku', 'kj', 'ky',
			'mrj', 'lad', 'lbe', 'lez', 'lo', 'ltg', 'la', 'lv', 'lb', 'lt', 'lij', 'li', 'ln', 'jbo', 'lg', 'lmo',
			'hu', 'mk', 'mg', 'ml', 'mt', 'mi', 'mr', 'xmf', 'arz', 'mzn', 'ms', 'cdo', 'mwl', 'mdf', 'mo', 'mn',
			'mus', 'my', 'nah', 'na', 'fj', 'nl', 'nds-nl', 'cr', 'ne', 'new', 'ja', 'nap', 'ce', 'frr', 'pih', 'no',
			'nb', 'nn', 'nrm', 'nov', 'ii', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pfl', 'pag', 'pnb',
			'pap', 'ps', 'koi', 'km', 'pcd', 'pms', 'tpi', 'nds', 'pl', 'tokipona', 'tp', 'pnt', 'pt', 'aa', 'kaa',
			'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'rue', 'ru', 'sah', 'se', 'sm', 'sa', 'sg', 'sc', 'sco',
			'stq', 'st', 'nso', 'tn', 'sq', 'scn', 'si', 'simple', 'sd', 'ss', 'sk', 'sl', 'cu', 'szl', 'so',
			'ckb', 'srn', 'sr', 'sh', 'su', 'fi', 'sv', 'tl', 'ta', 'shi', 'kab', 'roa-tara', 'tt', 'te', 'tet',
			'th', 'ti', 'tg', 'to', 'chr', 'chy', 've', 'tr', 'tk', 'tw', 'udm', 'bug', 'uk', 'ur', 'ug', 'za',
			'vec', 'vep', 'vi', 'vo', 'fiu-vro', 'wa', 'zh-classical', 'vls', 'war', 'wo', 'wuu', 'ts', 'yi',
			'yo', 'zh-yue', 'diq', 'zea', 'bat-smg', 'zh', 'zh-tw', 'zh-cn',
		);

		static $order_alphabetic_revised = array(
			'ace', 'kbd', 'af', 'ak', 'als', 'am', 'ang', 'ab', 'ar', 'an', 'arc', 'roa-rup', 'frp', 'as', 'ast',
			'gn', 'av', 'ay', 'az', 'bjn', 'id', 'ms', 'bm', 'bn', 'zh-min-nan', 'nan', 'map-bms', 'jv', 'su',
			'ba', 'be', 'be-x-old', 'bh', 'bcl', 'bi', 'bar', 'bo', 'bs', 'br', 'bug', 'bg', 'bxr', 'ca', 'ceb',
			'cv', 'cs', 'ch', 'cbk-zam', 'ny', 'sn', 'tum', 'cho', 'co', 'cy', 'da', 'dk', 'pdc', 'de', 'dv', 'nv',
			'dsb', 'na', 'dz', 'mh', 'et', 'el', 'eml', 'en', 'myv', 'es', 'eo', 'ext', 'eu', 'ee', 'fa', 'hif',
			'fo', 'fr', 'fy', 'ff', 'fur', 'ga', 'gv', 'sm', 'gag', 'gd', 'gl', 'gan', 'ki', 'glk', 'gu', 'got',
			'hak', 'xal', 'ko', 'ha', 'haw', 'hy', 'hi', 'ho', 'hsb', 'hr', 'io', 'ig', 'ilo', 'bpy', 'ia', 'ie',
			'iu', 'ik', 'os', 'xh', 'zu', 'is', 'it', 'he', 'kl', 'kn', 'kr', 'pam', 'ka', 'ks', 'csb', 'kk', 'kw',
			'rw', 'ky', 'rn', 'mrj', 'sw', 'kv', 'kg', 'ht', 'ku', 'kj', 'lad', 'lbe', 'lez', 'lo', 'la', 'ltg',
			'lv', 'to', 'lb', 'lt', 'lij', 'li', 'ln', 'jbo', 'lg', 'lmo', 'hu', 'mk', 'mg', 'ml', 'krc', 'mt',
			'mi', 'mr', 'xmf', 'arz', 'mzn', 'cdo', 'mwl', 'koi', 'mdf', 'mo', 'mn', 'mus', 'my', 'nah', 'fj',
			'nl', 'nds-nl', 'cr', 'ne', 'new', 'ja', 'nap', 'ce', 'frr', 'pih', 'no', 'nb', 'nn', 'nrm', 'nov',
			'ii', 'oc', 'mhr', 'or', 'om', 'ng', 'hz', 'uz', 'pa', 'pi', 'pfl', 'pag', 'pnb', 'pap', 'ps', 'km',
			'pcd', 'pms', 'nds', 'pl', 'pnt', 'pt', 'aa', 'kaa', 'crh', 'ty', 'ksh', 'ro', 'rmy', 'rm', 'qu', 'ru',
			'rue', 'sah', 'se', 'sa', 'sg', 'sc', 'sco', 'stq', 'st', 'nso', 'tn', 'sq', 'scn', 'si', 'simple', 'sd',
			'ss', 'sk', 'sl', 'cu', 'szl', 'so', 'ckb', 'srn', 'sr', 'sh', 'fi', 'sv', 'tl', 'ta', 'shi', 'kab',
			'roa-tara', 'tt', 'te', 'tet', 'th', 'vi', 'ti', 'tg', 'tpi', 'tokipona', 'tp', 'chr', 'chy', 've', 'tr',
			'tk', 'tw', 'udm', 'uk', 'ur', 'ug', 'za', 'vec', 'vep', 'vo', 'fiu-vro', 'wa', 'zh-classical', 'vls',
			'war', 'wo', 'wuu', 'ts', 'yi', 'yo', 'zh-yue', 'diq', 'zea', 'bat-smg', 'zh', 'zh-tw', 'zh-cn',
		);

		$sort = \Wikibase\Settings::get( 'sort' );
		switch( $sort ) {
			case 'code':
				self::$sort_order = $order_alphabetic;
				sort( self::$sort_order );
				break;
			case 'alphabetic':
				self::$sort_order = $order_alphabetic;
				break;
			case 'alphabetic_revised':
				self::$sort_order = $order_alphabetic_revised;
				break;
			case 'none':
			default:
				self::$sort_order = false;
				return false;
		}

		$sortPrepend = \Wikibase\Settings::get( 'sortPrepend' );
		if( is_array( $sortPrepend ) ) {
			self::$sort_order = array_unique( array_merge( $sortPrepend, self::$sort_order ) );
		}
		self::$sort_order = array_flip( self::$sort_order );

		return true;
	}

}
