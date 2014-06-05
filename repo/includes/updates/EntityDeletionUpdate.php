<?php

namespace Wikibase;
use Title;
use Wikibase\Repo\WikibaseRepo;

/**
 * Deletion update to handle deletion of Wikibase entities.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDeletionUpdate extends \DataUpdate {

	/**
	 * @since 0.1
	 *
	 * @var EntityContent
	 */
	private $content;

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 * @param Title $title
	 */
	public function __construct( EntityContent $content, Title $title ) {
		$this->content = $content;
		$this->title = $title;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 0.1
	 */
	public final function doUpdate() {
		wfProfileIn( __METHOD__ );

		$store = WikibaseRepo::getDefaultInstance()->getStore();
		$entity = $this->content->getEntity();

		$store->getTermIndex()->deleteTermsOfEntity( $entity->getId() );
		$this->doTypeSpecificStuff( $store, $entity );

		$store->newEntityPerPage()->deleteEntityPage(
			$entity->getId(),
			$this->title->getArticleID()
		);

		/**
		 * Gets called after the deletion of an item has been committed,
		 * allowing for extensions to do additional cleanup.
		 *
		 * @since 0.5
		 *
		 * @param EntityContent $entityContent
		 */
		wfRunHooks( 'WikibaseEntityDeletionUpdate', array( $this->content ) );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Do anything specific to the entity type.
	 *
	 * @since 0.1
	 *
	 * @param Store $store
	 * @param Entity $entity
	 */
	protected function doTypeSpecificStuff( Store $store, Entity $entity ) {
		// Override to add behavior.
	}

}
