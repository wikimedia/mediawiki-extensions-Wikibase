<?php

namespace Wikibase;

/**
 * Handles the view action for Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Action
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler < daniel.kinzler@wikimedia.de >
 */
class ViewItemAction extends ViewEntityAction {

	/**
	 * @see FormlessAction::show()
	 *
	 * @since 0.1
	 */
	public function show() {
		parent::show();

		$content = $this->getContent();

		if ( !is_null( $content ) ) {
			ItemView::registerJsConfigVars(
				$this->getOutput(),
				$this->getContent(),
				$this->getLanguage()->getCode()
			);
		}
	}

}