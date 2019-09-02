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
		$contentLanguages['term'] = self::getDefaultTermsLanguages();
		$contentLanguages['monolingualtext'] = self::getDefaultMonolingualTextLanguages();

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
					'alc', // T190981
					'ami', // T174238
					'bnn', // T174230
					'brx', // T155369
					'chn', // T155370
					'clc', // T222020
					'cnr', // T185800
					'cop', // T155371
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
					'lkt', // T125066
					'lld', // T125066
					'mid', // T155418
					'mnc', // T137808
					'moe', // T151129
					'non', // T137115
					'nr', // T155430
					'nxm', // T167745
					'ood', // T155423
					'otk', // T137809
					'peo', // T189427
					'pjt', // T155426
					'ppu', // T174233
					'pwn', // T174231
					'pyu', // T174227
					'quc', // T155376
					'qya', // T185194
					'rar', // T155427
					'shy', // T184783
					'sia', // T217521
					'sjd', // T188596
					'sjk', // T217521
					'sjn', // T185194
					'sjt', // T217521
					'sju', // T188599
					'smn', // T188580
					'sms', // T188579
					'ssf', // T174236
					'syc', // T164580
					'tlb', // T216798
					'tli', // T230145
					'trv', // T174228
					'tzl', // T98314
					'uga', // T155431
					'umu', // T160531
					'uun', // T174229
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
