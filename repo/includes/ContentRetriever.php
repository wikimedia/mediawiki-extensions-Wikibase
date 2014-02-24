<?php

namespace Wikibase;

use Article;
use Content;
use Revision;
use Title;
use WebRequest;

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
	 * Returns the content to display on a page, given request params.
	 *
	 * If it is a diff request, then display the revision specified
	 * in the 'diff=' request param.
	 *
	 * @todo split out the get revision id stuff, add tests and see if
	 * any core code can be shared here
	 *
	 * @param Article $article
	 * @param Title $title
	 * @param WebRequest $request
	 *
	 * @return Content|null
	 */
	public function getContentForRequest( Article $article, Title $title, WebRequest $request ) {
		$queryValues = $request->getQueryValues();
		$oldId = $article->getOldID();

		if ( array_key_exists( 'diff', $queryValues ) ) {
			$revision = $this->getDiffRevision( $oldId, $queryValues['diff'] );
		} else {
			$revision = Revision::newFromTitle( $title, $oldId );
		}

		return $revision !== null ?
			$revision->getContent( Revision::FOR_THIS_USER ) : null;
	}

	/**
	 * Get the revision specified in the diff parameter or prev/next revision of oldid
	 *
	 * @since 0.5
	 *
	 * @param int $oldId
	 * @param string|int $diffValue
	 *
	 * @return Revision|null
	 */
	public function getDiffRevision( $oldId, $diffValue ) {
		$oldRevision = Revision::newFromId( $oldId );

		if ( $diffValue === 'prev' ) {
			return $oldRevision;
		} else if ( $diffValue === 'next' ) {
			return $oldRevision->getNext();
		}

		// All remaining non-numeric values including 'cur' become 0, see DifferenceEngine
		$revId = intval( $diffValue );
		if ( $revId === 0 ) {
			$revId = $oldRevision->getTitle()->getLatestRevID();
		}
		return Revision::newFromId( $revId );
	}

}
