<?php

namespace Wikibase;

use Article;
use Revision;
use Title;
use WikiPage;

/**
 * Fetches content for a given Title / Article and request (diff or not diff)
 *
 * @since 0.5
 *
 * @todo put/merge this into core, with revision id / content fetching stuff
 * factored out of DifferenceEngine and Article classes. :)
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ContentRetriever {

	/**
	 * Returns the content of the page in the revision being viewed.
	 *
	 * @todo split out the get revision id stuff, add tests and see if
	 * any core code can be shared here
	 *
	 * @return Content|null
	 */
	public function getContent( $article, $title, $request ) {
		$queryValues = $request->getQueryValues();
		$oldId = $article->getOldID();

		if ( array_key_exists( 'diff', $queryValues ) ) {
			$revision = $this->getDiffRevision( $oldId, $queryValues['diff'] );
		} else {
			$revision = Revision::newFromTitle( $title, $oldId );
		}

		return $revision !== null ? $revision->getContent() : null;
	}

	/**
	 * Get the revision specified in the diff parameter or prev/next revision of oldid
	 *
	 * @since 0.4
	 *
	 * @param int $oldId
	 * @param string|int $diffValue
	 *
	 * @return Revision|null
	 */
	public function getDiffRevision( $oldId, $diffValue ) {
		if ( in_array( $diffValue, array( 'prev', 'next', 'cur', 0 ) ) ) {
			$oldIdRev = Revision::newFromId( $oldId );

			if ( $diffValue === 0 || $diffValue === 'cur' ) {
				$curId = $oldIdRev->getTitle()->getLatestRevID();
				return Revision::newFromId( $curId );
			} elseif ( $diffValue === 'next' ) {
				return $oldIdRev->getNext();
			}

			return $oldIdRev;
		} else {
			$revId = (int)$diffValue;
			return Revision::newFromId( $revId );
		}

		return null;
	}

}
