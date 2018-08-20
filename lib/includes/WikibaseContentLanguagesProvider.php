<?php

namespace Wikibase\Lib;

use Hooks;
use OutOfBoundsException;

/**
 * @license GPL 2+
 * @author Marius Hoch
 */
class WikibaseContentLanguagesProvider {

	/**
	 * @var ContentLanguages[] Context -> ContentLanguages map
	 */
	private $contentLanguages = [];

	/**
	 * @param string $context
	 * @return ContentLanguagess
	 */
	public function getContentLanguages( $context ) {
		if ( isset( $this->contentLanguages[$context] ) ) {
			return $this->contentLanguages[$context];
		}
		if ( $context === 'MonolingualText' ) {
			$this->contentLanguages[$context] = $this->getMonolingualTextLanguages();	
		}
		if ( $context === 'Terms' ) {
			$this->contentLanguages[$context] = $this->getTermsLanguages();	
		}
		if ( isset( $this->contentLanguages[$context] ) ) {
			return $this->contentLanguages[$context];
		}

		// TODO: DOCUMENT HOOK
		$result = null;
		Hooks::run( 'WikibaseContentLanguages', [ $context, &$result ] );
		
		if ( $result instanceof ContentLanguages ) {
			return $result;
		}

		throw new OutOfBoundsException( 'Unknown content language context: ' . $context );
	}

	/**
	 * @return ContentLanguages
	 */
	private function getMonolingualTextLanguages() {
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
					'ami', // T174238
					'bnn', // T174230
					'brx', // T155369
					'chn', // T155370
					'cnr', // T185800
					'cop', // T155371
					'ett', // T125066
					'eya', // T155372
					'fkv', // T125066
					'fos', // T174234
					'fr-ca', // T151186
					'frm', // T181823
					'fro', // T181823
					'fuf', // T155429
					'gez', // T155373
					'hai', // T138131
					'kjh', // T155377
					'koy', // T125066
					'lag', // T161983
					'lkt', // T125066
					'lld', // T125066
					'mnc', // T137808
					'moe', // T151129
					'non', // T137115
					'nr', // T155430
					'nxm', // T167745
					'ood', // T155423
					'otk', // T137809
					'pjt', // T155426
					'ppu', // T174233
					'pwn', // T174231
					'pyu', // T174227
					'quc', // T155376
					'shy', // T184783
					'sjd', // T188596
					'sju', // T188599
					'smn', // T188580
					'sms', // T188579
					'ssf', // T174236
					'trv', // T174228
					'tzl', // T98314
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
				'nl-informal',
			] )
		);
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 *
	 * @return ContentLanguages
	 */
	private function getTermsLanguages() {
		return new MediaWikiContentLanguages();
	}

}
