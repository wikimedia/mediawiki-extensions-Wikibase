<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiResult;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module to query available badge items.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadges extends ApiBase {

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );
		$this->getMain()->setCacheMaxAge( 3600 );

		$badgeItems = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'badgeItems' );
		$idStrings = array_keys( $badgeItems );
		ApiResult::setIndexedTagName( $idStrings, 'badge' );
		$this->getResult()->addValue(
			null,
			'badges',
			$idStrings
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbavailablebadges' =>
				'apihelp-wbavailablebadges-example-1',
		];
	}

}
