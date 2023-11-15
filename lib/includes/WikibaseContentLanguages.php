<?php

namespace Wikibase\Lib;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;
use OutOfRangeException;

/**
 * A collection of {@link ContentLanguages} objects for different contexts.
 *
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguages {

	public const CONTEXT_TERM = 'term';
	public const CONTEXT_MONOLINGUAL_TEXT = 'monolingualtext';

	/**
	 * @var ContentLanguages[]
	 */
	private $contentLanguages;

	public function __construct( array $contentLanguages ) {
		$this->contentLanguages = $contentLanguages;
	}

	/**
	 * @return string[]
	 */
	public function getContexts() {
		return array_keys( $this->contentLanguages );
	}

	/**
	 * @param string $context 'term', 'monolingualtext', …
	 * @return ContentLanguages
	 * @throws OutOfRangeException if the $context is unknown
	 */
	public function getContentLanguages( $context ) {
		if ( array_key_exists( $context, $this->contentLanguages ) ) {
			return $this->contentLanguages[$context];
		} else {
			throw new OutOfRangeException(
				'No ContentLanguages registered for context: ' . $context
			);
		}
	}

	public static function getDefaultInstance(
		HookContainer $hookContainer = null,
		LanguageNameUtils $languageNameUtils = null
	) {
		if ( $hookContainer === null ) {
			$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		}

		$contentLanguages = [];
		$contentLanguages[self::CONTEXT_TERM] = self::getDefaultTermsLanguages( $languageNameUtils );
		$contentLanguages[self::CONTEXT_MONOLINGUAL_TEXT] = self::getDefaultMonolingualTextLanguages( $languageNameUtils );

		$hookContainer->run(
			'WikibaseContentLanguages',
			[ &$contentLanguages ],
			[ 'abortable' => false ]
		);

		return new self( $contentLanguages );
	}

	public static function getDefaultTermsLanguages( LanguageNameUtils $languageNameUtils = null ) {
		// Note: this list is also the basis of getDefaultMonolingualTextLanguages(); custom
		// (non-MediaWikiContentLanguages) terms languages also become monolingual text languages.
		return new UnionContentLanguages(
			new MediaWikiContentLanguages( $languageNameUtils ),
			new StaticContentLanguages(
				[
					// FIXME: Until T273627 is fully resolved, languages added here should also be added to
					// wmgExtraLanguageNames in Wikimedia's mediawiki-config.
					'agq', // T288335
					'bag', // T263946
					'bas', // T263946
					'bax', // T263946
					'bbj', // T263946
					'bfd', // T263946
					'bkc', // T263946
					'bkh', // T263946
					'bkm', // T263946
					'bqz', // T263946
					'byv', // T263946
					'cak', // T278854
					'cal', // T308062
					'cnh', // T278853
					'dua', // T263946
					'en-us', // T154589
					'eto', // T263946
					'etu', // T263946
					'ewo', // T263946
					'fkv', // T167259
					'fmp', // T263946
					'gya', // T263946
					'isu', // T263946
					'kea', // T127435
					'ker', // T263946
					'ksf', // T263946
					'lem', // T263946
					'lns', // T263946
					'mcn', // T293884
					'mcp', // T263946
					'mua', // T263946
					'nan-hani', // T180771
					'nge', // T263946
					'nla', // T263946
					'nmg', // T263946
					'nnh', // T263946
					'nnz', // T263946
					'nod', // T93880
					'osa-latn', // T265297
					'ota', // T59342
					'pap-aw', // T275682
					'quc', // T278851
					'rmf', // T226701
					'rwr', // T61905
					'ryu', // T271215
					'sjd', // T226701
					'sje', // T146707
					'sju', // T226701
					'smj', // T146707
					'sms', // T220118, T223544
					'srq', // T113408
					'tpv', // T308062
					'tvu', // T263946
					'vut', // T263946
					'wes', // T263946
					'wya', // T283364
					'yas', // T263946
					'yat', // T263946
					'yav', // T263946
					'ybb', // T263946
				]
			)
		);
	}

	public static function getDefaultMonolingualTextLanguages( LanguageNameUtils $languageNameUtils = null ) {
		return new DifferenceContentLanguages(
			new UnionContentLanguages(
				self::getDefaultTermsLanguages( $languageNameUtils ),
				new MediaWikiContentLanguages( $languageNameUtils, LanguageNameUtils::ALL ),
			),
			// MediaWiki language codes we don't want for monolingual text values
			new StaticContentLanguages( [
				// Language codes that are not even well-formed BCP 47 language codes
				'simple',

				// Deprecated language codes with an alternative in MediaWiki
				'bat-smg', // => sgs
				'be-x-old', // => be-tarask
				'fiu-vro', // => vro
				'roa-rup', // => rup
				'zh-classical', // => lzh
				'zh-min-nan', // => nan
				'zh-yue', // => yue

				// Language codes we don't want for semantic reasons
				'de-formal',
				'es-formal',
				'hu-formal',
				'nl-informal',
			] )
		);
	}

}
