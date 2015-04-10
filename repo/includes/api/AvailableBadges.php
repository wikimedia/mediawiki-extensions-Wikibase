<?php

namespace Wikibase\Api;

use ApiBase;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to query available badge items.
 *
 * @todo this might also be useful to find badges suggestions based on labels
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadges extends ApiBase {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.5
	 */
	public function execute() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$badgeItems = $settings->getSetting( 'badgeItems' ) ?: array();
		$idStrings = array_keys( $badgeItems );

		$result = $this->getResult();
		$result->setIndexedTagName( $idStrings, 'badge' );
		$result->addValue( null, 'badges', $idStrings );
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbavailablebadges' =>
				'apihelp-wbavailablebadges-example-1',
		);
	}

}
