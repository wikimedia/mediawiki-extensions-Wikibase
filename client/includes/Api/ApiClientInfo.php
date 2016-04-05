<?php

namespace Wikibase\Client\Api;

use ApiQuery;
use ApiQueryBase;
use Wikibase\SettingsArray;

/**
 * Provides url and path information for the associated Wikibase repo
 *
 * @todo: may want to include namespaces and other settings here too.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class ApiClientInfo extends ApiQueryBase {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 * @param ApiQuery $apiQuery
	 * @param string $moduleName
	 */
	public function __construct( SettingsArray $settings, ApiQuery $apiQuery, $moduleName ) {
		parent::__construct( $apiQuery, $moduleName, 'wb' );

		$this->settings = $settings;
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$apiData = $this->getInfo( $params );

		$this->getResult()->addValue( 'query', 'wikibase', $apiData );
	}

	/**
	 * Gets repo url info to inject into the api module
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	private function getInfo( array $params ) {
		$data = [];

		foreach ( $params['prop'] as $p ) {
			switch ( $p ) {
				case 'url':
					$data['repo'] = array(
						'url' => $this->getRepoUrls()
					);
					break;
				case 'siteid':
					$data['siteid'] = $this->settings->getSetting( 'siteGlobalID' );
					break;
			}
		}

		return $data;
	}

	/**
	 * @return string[]
	 */
	private function getRepoUrls() {
		return array(
			'base' => $this->settings->getSetting( 'repoUrl' ),
			'scriptpath' => $this->settings->getSetting( 'repoScriptPath' ),
			'articlepath' => $this->settings->getSetting( 'repoArticlePath' ),
		);
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'prop' => array(
				self::PARAM_DFLT => 'url|siteid',
				self::PARAM_ISMULTI => true,
				self::PARAM_TYPE => array(
					'url', 'siteid'
				)
			),
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return array(
			'action=query&meta=wikibase'
				=> 'apihelp-query+wikibase-example',
		);
	}

}
