<?php

namespace Wikibase\Client\Api;

use ApiQuery;
use ApiQueryBase;
use Wikibase\Lib\SettingsArray;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Provides url and path information for the associated Wikibase repo
 *
 * @todo may want to include namespaces and other settings here too.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class ApiClientInfo extends ApiQueryBase {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @param ApiQuery $apiQuery
	 * @param string $moduleName
	 * @param SettingsArray $settings
	 */
	public function __construct( ApiQuery $apiQuery, $moduleName, SettingsArray $settings ) {
		parent::__construct( $apiQuery, $moduleName, 'wb' );

		$this->settings = $settings;
	}

	/**
	 * @inheritDoc
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
					$data['repo'] = [
						'url' => $this->getRepoUrls(),
					];
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
		return [
			'base' => $this->settings->getSetting( 'repoUrl' ),
			'scriptpath' => $this->settings->getSetting( 'repoScriptPath' ),
			'articlepath' => $this->settings->getSetting( 'repoArticlePath' ),
		];
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'url|siteid',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'url', 'siteid',
				],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&meta=wikibase'
				=> 'apihelp-query+wikibase-example',
		];
	}

}
