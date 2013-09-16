<?php

namespace Wikibase\Api;

use Title;
use ValueParsers\ParseException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\Api\WikibaseApiTestCase;

/**
 * Helper class for modifying entities
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class EntityModificationHelper {

	/**
	 * @since 0.5
	 *
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @since 0.5
	 *
	 * @param \ApiMain $apiMain
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		\ApiMain $apiMain,
		EntityIdParser $entityIdParser
	) {
		$this->apiMain = $apiMain;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @param string $entityIdParam
	 *
	 * @return EntityId
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		try {
			return $this->entityIdParser->parse( $entityIdParam );
		} catch ( ParseException $parseException ) {
			$this->apiMain->dieUsage( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return Title
	 */
	public function getEntityTitleFromEntityId( EntityId $entityId ) {
		$entityTitle = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->apiMain->dieUsage( 'No such entity' , 'no-such-entity' );
		}

		return $entityTitle;
	}

	/**
	 * Load the entity content of the given revision.
	 *
	 * Will fail by calling dieUsage() if the revision can not be found or can not be loaded.
	 *
	 * @param Title   $title   : the title of the page to load the revision for
	 * @param bool|int $revId   : the revision to load. If not given, the current revision will be loaded.
	 * @param int      $audience
	 * @param \User    $user
	 * @param int      $audience: the audience to load this for, see Revision::FOR_XXX constants and
	 *                          Revision::getContent().
	 * @param \User    $user    : the user to consider if $audience == Revision::FOR_THIS_USER
	 *
	 * @return \Wikibase\EntityContent the revision's content.
	 */
	public function getEntityContent( Title $title, $revId = false,
										  $audience = \Revision::FOR_PUBLIC,
										  \User $user = null
	) {
		if ( $revId === null || $revId === false || $revId === 0 ) {
			$page = \WikiPage::factory( $title );
			$content = $page->getContent( $audience, $user );
		} else {
			$revision = \Revision::newFromId( $revId );

			if ( !$revision ) {
				$this->apiMain->dieUsage( "Revision not found: $revId", 'nosuchrevid' );
			}

			if ( $revision->getPage() != $title->getArticleID() ) {
				$this->apiMain->dieUsage( "Revision $revId does not belong to " .
				$title->getPrefixedDBkey(), 'nosuchrevid' );
			}

			$content = $revision->getContent( $audience, $user );
		}

		if ( is_null( $content ) ) {
			$this->apiMain->dieUsage( "Can't access item content of " .
			$title->getPrefixedDBkey() .
			", revision may have been deleted.", 'cant-load-entity-content' );
		}

		return $content;
	}

}