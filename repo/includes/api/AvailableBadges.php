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
		$badgeItems = array_keys( $settings->getSetting( 'badgeItems' ) );
		$this->getResult()->setIndexedTagName( $badgeItems, 'badge' );
		$this->getResult()->addValue(
			null,
			'badges',
			$badgeItems
		);
	}

	/**
	 * @deprecated since MediaWiki core 1.25
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
	 * @deprecated since MediaWiki core 1.25
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

	/**
	 * @see ApiBase:getExamplesMessages()
	 *
	 * @return array
	 */
	protected function getExamplesMessages() {
		return array(
		       'action=wbavailablebadges' =>
		       		'apihelp-wbavailablebadges-example-1',
		);
	}
}