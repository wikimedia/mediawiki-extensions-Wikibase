<?php

namespace Wikibase\Api;

use Wikibase\Repo\WikibaseRepo;

/**
 * API module to query available badges.
 *
 * @todo this might also be useful to find badges suggestions based on labels
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadges extends ApiWikibase {

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.5
	 */
	public function execute() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$this->getResult()->addValue(
			null,
			'badges',
			array_keys( $settings->getSetting( 'badgeItems' ) )
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
			'API module to query available badges.'
		);
	}

	/**
	 * @see ApiBase::getHelpUrls()
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbavailablebadges';
	}

}