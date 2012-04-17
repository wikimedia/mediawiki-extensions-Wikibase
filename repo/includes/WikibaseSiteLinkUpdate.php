<?php

/**
 * Takes care of updating the secondary storage table wb_items_per_site.
 *
 * @since 0.1
 *
 * @file WikibaseSiteLinkUpdate.php
 * @ingroup Wikibase
 * @ingroup SecondaryDataUpdate
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseSiteLinkUpdate extends SecondaryDataUpdate {

	// TODO

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param $labels
	 */
	public function __construct( Title $title, $labels ) { // TODO
		$this->title = $title;
		$this->labels = $labels;
	}

	/**
	 * Perform the actual update.
	 */
	public function doUpdate() {
		// TODO
	}

}
