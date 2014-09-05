<?php

namespace Wikibase\Repo\View;

/**
 * Class to generate the toc of an entity view.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TocGenerator {

	/**
	 * Builds and returns the HTML for the toc.
	 *
	 * @param string[] $tocSections array( link target => system message key )
	 *
	 * @return string
	 */
	public function getHtmlForToc( array $tocSections ) {
		$tocContent = '';

		if ( count( $tocSections ) < 3 ) {
			// Including the marker for the termbox toc entry, there is fewer
			// 3 sections. MediaWiki core doesn't show a TOC unless there are
			// at least 3 sections, so we shouldn't either.
			return '';
		}

		$i = 1;

		foreach ( $tocSections as $id => $messageKey ) {
			$message = wfMessage( $messageKey );
			if ( $message->exists() ) {
				$tocContent .= wfTemplate( 'wb-entity-toc-section',
					$i++,
					$id,
					$message->text()
				);
			} else {
				// Trick to allow addition of text injector markers
				$tocContent .= $messageKey;
			}
		}

		$toc = wfTemplate( 'wb-entity-toc',
			wfMessage( 'toc' )->text(),
			$tocContent
		);

		return $toc;
	}

}
