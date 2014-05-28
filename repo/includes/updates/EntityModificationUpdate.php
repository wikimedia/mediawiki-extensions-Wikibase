<?php

namespace Wikibase;
use Wikibase\Repo\WikibaseRepo;

/**
 * Represents an update to the structured storage for a single Entity.
 * TODO: we could keep track of actual changes in a lot of cases, and so be able to do less (expensive) queries to update.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityModificationUpdate extends \DataUpdate {

	/**
	 * @since 0.1
	 *
	 * @var EntityContent
	 */
	protected $newContent;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $newContent
	 */
	public function __construct( EntityContent $newContent ) {
		$this->newContent = $newContent;
	}

	/**
	 * Perform the actual update.
	 *
	 * @since 0.1
	 */
	public function doUpdate() {
		wfProfileIn( __METHOD__ );

		$this->updateRepoStore();
		$this->fireHook();

		wfProfileOut( __METHOD__ );
	}

	protected function updateRepoStore() {
		$store = WikibaseRepo::getDefaultInstance()->getStore();
		$entity = $this->newContent->getEntity();

		$store->getTermIndex()->saveTermsOfEntity( $entity );
		$this->doTypeSpecificStuff( $store, $entity );
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

	protected function fireHook() {
		/**
		 * Gets called after the structured save of an item has been committed,
		 * allowing for extensions to do additional storage/indexing.
		 *
		 * @since 0.5
		 *
		 * @param EntityContent $newEntityContent
		 */
		wfRunHooks( 'WikibaseEntityModificationUpdate', array( $this->newContent ) );
	}

}
