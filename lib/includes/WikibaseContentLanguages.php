<?php

namespace Wikibase\Lib;

use Hooks;
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

	public static function getDefaultInstance() {
		$contentLanguages = [];
		$contentLanguages[self::CONTEXT_TERM] = self::getDefaultTermsLanguages();
		$contentLanguages[self::CONTEXT_MONOLINGUAL_TEXT] = self::getDefaultMonolingualTextLanguages();

		Hooks::runWithoutAbort( 'WikibaseContentLanguages', [ &$contentLanguages ] );

		return new self( $contentLanguages );
	}

	public static function getDefaultTermsLanguages() {
		return new MediaWikiContentLanguages();
	}

	public static function getDefaultMonolingualTextLanguages() {
		// This has to be a superset of the language codes returned by
		// wikibase.WikibaseContentLanguages.
		// We don't want to have language codes in the suggester that are not
		// supported by the backend. The other way round is currently acceptable,
		// but will be fixed in T124758.
		return new DifferenceContentLanguages(
			new UnionContentLanguages(
				new MediaWikiContentLanguages(),
				new StaticContentLanguages( [
					// Special ISO 639-2 codes
					'und', 'mis', 'mul', 'zxx',

					// Other valid codes without MediaWiki localization
					'abe', // T150633
					'abq', // T155367
					'abq-latn', // T155424
					'alc', // T190981
					'bdr', // T234330
					'bnn', // T174230
					'brx', // T155369
					'ccp', // T210311
					'chn', // T155370
					'ckt', // T240097
					'clc', // T222020
					'cnr', // T185800
					'cop', // T155371
					'crb', // T220284
					'dag', // T240098
					'el-cy', // T198674
					'ett', // T125066
					'eya', // T155372
					'fkv', // T125066
					'fos', // T174234
					'fr-ca', // T151186
					'frm', // T181823
					'fro', // T181823
					'fuf', // T155429
					'gez', // T155373
					'gmy', // T155421
					'hai', // T138131
					'haz', // T155374
					'hbo', // T155368
					'kjh', // T155377
					'kld', // T198366
					'koy', // T125066
					'lag', // T161983
					'lcm', // T234761
					'lkt', // T125066
					'mfa', // T235468
					'mid', // T155418
					'mnc', // T137808
					'moe', // T151129
					'non', // T137115
					'nr', // T155430
					'nrf-gg', // T165648
					'nrf-je', // T165648
					'nsk', // T250246
					'nxm', // T167745
					'ood', // T155423
					'otk', // T137809
					'peo', // T189427
					'pi-sidd', // T230881
					'pjt', // T155426
					'ppu', // T174233
					'pwn', // T174231
					'pyu', // T174227
					'quc', // T155376
					'qya', // T185194
					'rar', // T155427
					'rm-puter', // T222426
					'rm-rumgr', // T222426
					'rm-surmiran', // T222426
					'rm-sursilv', // T222426
					'rm-sutsilv', // T222426
					'rm-vallader', // T222426
					'rmf', // T226701
					'sa-sidd', // T230881
					'shy', // T184783
					'sia', // T217521
					'sjd', // T188596
					'sjk', // T217521
					'sjn', // T185194
					'sjt', // T217521
					'sju', // T188599
					'sms', // T188579
					'ssf', // T174236
					'syc', // T164580
					'tlb', // T216798
					'tli', // T230145
					'tnq', // T220284
					'tzl', // T98314
					'uga', // T155431
					'umu', // T160531
					'uun', // T174229
					'wls', // T239411
					'xpu', // T167811
					'yap', // T155433
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
