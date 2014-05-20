<?php

namespace Wikibase\Api;

use Wikibase\Repo\WikibaseRepo;
use ApiBase;

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
		$badgeItems = array_keys( $settings->getSetting( 'badgeItems' ) );
		$this->getResult()->setIndexedTagName( $badgeItems, 'badge' );
		$this->getResult()->addValue(
			null,
			'badges',
			$badgeItems
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getDescription() {
		return array(
			'API module to query available badge items.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbavailablebadges' =>
				'Queries all available badge items',
		);
	}

}