<?php

namespace Wikibase\Api;

use Site;
use SiteSQLStore;
use Wikibase\Settings;

/**
 * API module to get sites that can be used on the wikibase installation
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class GetSites extends ApiWikibase {

	public function __construct( $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
		$this->siteLinkTargetProvider = new SiteLinkTargetProvider( SiteSQLStore::newInstance() );
	}

	public function execute() {
		$sites = $this->siteLinkTargetProvider->getSiteList( Settings::get( 'siteLinkGroups' ) );
		/** @var $site Site */
		foreach( $sites as $site ) {
			$siteArray = array(
				'globalId' => $site->getGlobalId(),
				'domain' => $site->getDomain(),
				'group' => $site->getGroup(),
				'language' => $site->getLanguageCode(),
			);
			$this->getResult()->addValue( 'sites', $site->getGlobalId(), $siteArray );
		}
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return 'API module to list sites that can be used on the wikibase installation.';
	}

	/**
	 * @see \ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbgetsites' =>
				'List sites that can be used on the wikibase installation.',
		);
	}

}