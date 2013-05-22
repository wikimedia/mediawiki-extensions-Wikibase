<?php

namespace Wikibase;
use ApiBase;

/**
 * Provides url and path information for the associated Wikibase repo
 *
 * @todo: may want to include namespaces and other settings here too.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiClientInfo extends \ApiQueryBase {

	protected $settings;

	/**
	 * @since 0.4
	 *
	 * @param ApiBase $api
	 * @param string $moduleName
	 */
	public function __construct( $api, $moduleName ) {
		parent::__construct( $api, $moduleName, 'wb' );

		// @todo inject this instead of using singleton here
		$this->settings = Settings::singleton();
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
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function getAllowedParams() {
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
	 * @see ApiBase::getHelpUrls
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:WikibaseClient#API';
	}

	/**
	 * @see ApiBase::getVersion
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

	/**
	 * @see ApiBase::getDescription
	 *
	 * @since 0.4i
	 *
	 * @return string
	 */
	public function getDescription() {
		return 'Get information about the Wikibase repository';
	}

}
