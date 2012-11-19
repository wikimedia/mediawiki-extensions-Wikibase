<?php
namespace Wikibase;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class UpdatePageJob extends \Job {

	/**
	 * Updates a page and registers change with recent changes
	 *
	 * @since 0.3
	 *
	 * @param \Title $title  The Title of the page to update
	 * @param array $params with changeId
	 * @param int $id
	 *
	 * @return bool
	 */
	function __construct( \Title $title, $params, $id = 0 ) {
		parent::__construct( 'updatePage', $title, $params, $id );
	}

	function run() {
		wfProfileIn( "Wikibase-" . __METHOD__ );

		if ( !$this->title->exists() ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return false;
		}

		$this->title->invalidateCache();

		if ( Settings::get( 'injectRecentChanges' )  === false ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return true;
		}

		$change = ChangesTable::singleton()->selectRow(
			null,
			array( 'id' => $this->params['changeId'] )
		);
		$rcinfo = $change->getMetadata();

		if ( ! is_array( $rcinfo ) ) {
			wfProfileOut( "Wikibase-" . __METHOD__ );
			return false;
		}

		$fields = $change->getFields(); //@todo: Fixme: add getFields() to the interface, or provide getters!
		$fields['entity_type'] = $change->getEntityType();
		unset( $fields['info'] );

		$rcparams = array(
			'wikibase-repo-change' => array_merge( $fields, $rcinfo )
		);

		$rc = ExternalRecentChange::newFromAttribs( $rcparams, $this->title );

		// todo: avoid reporting the same change multiple times when re-playing repo changes! how?!
		$rc->save();

		wfProfileOut( "Wikibase-" . __METHOD__ );
		return true;
	}

}
