<?php

namespace Wikibase;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use Wikibase\Client\WikibaseClient;

/**
 * Provides url and path information for the associated Wikibase repo
 *
 * @todo: may want to include namespaces and other settings here too.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiClientInfo extends ApiQueryBase {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @since 0.4
	 *
	 * @param ApiQuery $apiQuery
	 * @param string $moduleName
	 */
	public function __construct( ApiQuery $apiQuery, $moduleName ) {
		parent::__construct( $apiQuery, $moduleName, 'wb' );

		// @todo inject this instead of using singleton here
		$this->settings = WikibaseClient::getDefaultInstance()->getSettings();
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.4
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$apiData = $this->getRepoInfo( $params );

		$this->getResult()->addValue( 'query', 'wikibase', $apiData );
	}

	/**
	 * Set settings for api module
	 *
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 */
	public function setSettings( SettingsArray $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Gets repo url info to inject into the api module
	 *
	 * @since 0.4
	 *
	 * @param array $params[]
	 *
	 * @return array
	 */
	public function getRepoInfo( array $params ) {
		$data = array( 'repo' => array() );

		$repoUrlArray = array(
			'base' => $this->settings->getSetting( 'repoUrl' ),
			'scriptpath' => $this->settings->getSetting( 'repoScriptPath' ),
			'articlepath' => $this->settings->getSetting( 'repoArticlePath' ),
		);

		foreach ( $params['prop'] as $p ) {
			switch ( $p ) {
				case 'url':
					$data['repo']['url'] = $repoUrlArray;
					break;
				default;
					break;
			}
		}

		return $data;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array(
			'prop' => array(
				ApiBase::PARAM_DFLT => 'url',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'url',
				)
			),
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'prop' => array(
				'Which wikibase repository properties to get:',
				' url          - Base url, script path and article path',
			),
		);
	}

	/**
	 * @see ApiBase::getExamples
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=query&meta=wikibase' =>
				'Get url path and other info for the Wikibase repo',
		);
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Get information about the Wikibase repository.';
	}

}
