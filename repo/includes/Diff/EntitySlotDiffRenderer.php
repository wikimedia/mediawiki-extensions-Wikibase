<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Diff;

use Content;
use OutputPage;
use SlotDiffRenderer;
use Wikibase\Repo\Content\EntityContent;

/**
 * @license GPL-2.0-or-later
 */
class EntitySlotDiffRenderer extends SlotDiffRenderer {
	private EntityDiffVisualizer $diffVisualizer;

	private string $langCode;

	/**
	 * @param EntityDiffVisualizer $diffVisualizer
	 * @param string $langCode The language code which will be used by $diffVisualizer
	 */
	public function __construct( EntityDiffVisualizer $diffVisualizer, string $langCode ) {
		$this->diffVisualizer = $diffVisualizer;
		$this->langCode = $langCode;
	}

	public function getDiff( Content $oldContent = null, Content $newContent = null ) {
		$this->normalizeContents( $oldContent, $newContent, [ EntityContent::class ] );
		'@phan-var EntityContent $oldContent'; /** @var EntityContent $oldContent */
		'@phan-var EntityContent $newContent'; /** @var EntityContent $newContent */

		$diff = $oldContent->getDiff( $newContent );
		return $this->diffVisualizer->visualizeEntityContentDiff( $diff );
	}

	public function addModules( OutputPage $output ) {
		// add Wikibase styles, the diff may include entity links with labels, including fallback indicators
		$output->addModuleStyles( [ 'wikibase.alltargets' ] );
	}

	public function getExtraCacheKeys() {
		return [ "lang-{$this->langCode}" ];
	}
}
