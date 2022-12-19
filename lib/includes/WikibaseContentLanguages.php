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
				new StaticContentLanguages( [
					// Special ISO 639-2 codes
					'und', 'mis', 'mul', 'zxx',

					// Other valid codes without MediaWiki localization
					'abe', // T150633
					'abq', // T155367
					'abq-latn', // T155424
					'alc', // T190981
					'alq', // T295738
					'bdr', // T234330
					'bnn', // T174230
					'brx', // T155369
					'cal', // T266423
					'ccp', // T210311
					'cdo-hani', // T180771
					'chn', // T155370
					'ckt', // T240097
					'clc', // T222020
					'cmg', // T215032
					'cnr', // T185800
					'cop', // T155371
					'crb', // T220284
					'crl', // T264532
					'dru', // T267915
					'el-cy', // T198674
					'en-au', // T286862
					'en-in', // T212313
					'enm', // T298612
					'ett', // T125066
					'eya', // T155372
					'fkv', // T125066
					'fos', // T174234
					'fr-ca', // T151186
					'frm', // T181823
					'fro', // T181823
					'fuf', // T155429
					'gez', // T155373
					'gil', // T241424
					'gmh', // T295879
					'gml', // T217131
					'gmy', // T155421
					'gsw-fr', // T262922
					'hai', // T138131
					'hak-hans', // T180771
					'hak-hant', // T180771
					'haz', // T155374
					'hbo', // T155368
					'ja-hani', // T195816
					'ja-hira', // T195816
					'ja-hrkt', // T195816
					'ja-kana', // T195816
					'kjh', // T155377
					'kld', // T198366
					'koy', // T125066
					'lag', // T161983
					'lcm', // T234761
					'lij-mc', // T254968
					'lkt', // T125066
					'mfa', // T235468
					'mic', // T258331
					'mid', // T155418
					'mnc', // T137808
					'moe', // T151129
					'non', // T137115
					'non-runr', // T265782
					'nr', // T155430
					'nrf-gg', // T165648
					'nrf-je', // T165648
					'nsk', // T250246
					'nxm', // T167745
					'obt', // T319125
					'oj', // T268431
					'ojp', // T195816
					'ojp-hani', // T195816
					'ojp-hira', // T195816
					'oma', // T265296
					'ood', // T155423
					'otk', // T137809
					'peo', // T189427
					'phn-latn', // T155425
					'phn-phnx', // T155425
					'pi-sidd', // T230881
					'pjt', // T155426
					'ppu', // T174233
					'pyu', // T174227
					'quc', // T155376
					'qya', // T185194
					'rah', // T267479
					'rar', // T155427
					'rm-puter', // T222426
					'rm-rumgr', // T222426
					'rm-surmiran', // T222426
					'rm-sursilv', // T222426
					'rm-sutsilv', // T222426
					'rm-vallader', // T222426
					'rmf', // T226701
					'sa-sidd', // T230881
					'ser', // T312904
					'sia', // T217521
					'sjd', // T188596
					'sjk', // T217521
					'sjn', // T185194
					'sjt', // T217521
					'sju', // T188599
					'sms', // T188579
					'ssf', // T174236
					'sth', // T294922
					'syc', // T164580
					'tlb', // T216798
					'tli', // T230145
					'tnq', // T220284
					'tzl', // T98314
					'uga', // T155431
					'umu', // T160531
					'uun', // T174229
					'xbm', // T319125
					'xpu', // T167811
					'yap', // T155433
					'yec', // T296504
					'ykg' , // T252198
					'zun', // T155435
				] )
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
