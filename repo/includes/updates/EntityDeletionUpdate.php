<?php

namespace Wikibase;

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
	protected $content;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 */
	public function __construct( EntityContent $content ) {
		$this->content = $content;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 0.1
	 */
	public final function doUpdate() {
		wfProfileIn( __METHOD__ );

		$store = StoreFactory::getStore();
		$entity = $this->content->getEntity();

		$store->getTermIndex()->deleteTermsOfEntity( $entity );
		$this->doTypeSpecificStuff( $store, $entity );

		$store->newEntityPerPage()->deleteEntityPage(
			$entity->getId(),
			$this->content->getTitle()->getArticleID()
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
