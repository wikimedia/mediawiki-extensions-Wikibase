<?php

namespace Wikibase;

use Article;
use Content;
use Revision;
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
 * @author Thiemo Mättig
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
	 * @param WebRequest $request
	 * @param Article $article
	 *
	 * @return Content|null
	 */
	public function getContentForRequest( WebRequest $request, Article $article ) {
		$revision = $article->getRevisionFetched();

		// check for delete or unavailable revision
		if ( !$revision ) {
			return null;
		}

		if ( $request->getCheck( 'diff' ) ) {
			$oldId = $revision->getId();
			$diffValue = $request->getVal( 'diff' );
			$revision = $this->resolveDiffRevision( $oldId, $diffValue, $article );
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
	 * @param int|string $diffValue
	 * @param Article $article
	 *
	 * @return Revision|null
	 */
	protected function resolveDiffRevision( $oldId, $diffValue, Article $article )
	{
		$newid = $this->resolveNewid( $oldId, $diffValue, $article );

		if ( !$newid ) {
			$newid = $article->getTitle()->getLatestRevID();
		}

		return Revision::newFromId( $newid );
	}

	/**
	 * Get the revision ID specified in the diff parameter or prev/next revision of oldid
	 *
	 * @since 0.5
	 *
	 * @param int $oldId
	 * @param int|string $diffValue
	 * @param Article $article
	 *
	 * @return Bool|int
	 */
	protected function resolveNewid( $oldId, $diffValue, Article $article )
	{
		$contentHandler = $article->getRevisionFetched()->getContentHandler();
		$context = $article->getContext();
		$differenceEngine = $contentHandler->createDifferenceEngine( $context, $oldId, $diffValue );
		return $differenceEngine->getNewid();
	}

}
