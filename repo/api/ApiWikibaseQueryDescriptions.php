<?php
/**
 *
 *
 * Created on Apr 12, 2012
 *
 * Copyright © 2012
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
 * @file
 */

/**
 * API module to get the list of descriptions for a single Wikibase item.
 *
 * @since 0.1
 *
 * @file ApiWikibaseQueryDescriptions.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseQueryDescriptions extends ApiQueryBase {

	/**
	 * @var ApiResult
	 */
	private $result;

	private $limit;
	private $fld_language = false,
			$fld_description = false;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'wbd' );
	}

	public function execute() {
		$params = $this->extractRequestParams();

		$prop = array_flip( $params['prop'] );

		$this->fld_language = isset( $prop['language'] );
		$this->fld_description = isset( $prop['description'] );

		$this->limit = $params['limit'];
		$this->result = $this->getResult();
		
		$descriptions = array();
		$result = $this->getResult();
		
		// TODO: Stuff to be added later
		
		if ( count( $descriptions ) ) {
			# Add any remaining properties to the results
			$this->addItemDescriptions( $result, $currentPage, $descriptions );
		}
	
		// If we are testing we add some dummy data
		// TODO: Remove this when we go into production
		if ( isset($params['test']) ) {
			$list = array(
				array('language'=>'da', 'description'=>'Testing en to tre'),
				array('language'=>'de', 'description'=>'Testen ein zwei drei'),
				array('language'=>'en', 'description'=>'Testing one two three'),
				array('language'=>'no', 'description'=>'Testing en to tre'),
				array('language'=>'nn', 'description'=>'Testing ein to tre'),
				array('language'=>'sv', 'description'=>'Testing ett två tre'),
			);
			for ($i=0, $l = count($list); $i<$l; $i++) {
				if ( !$this->fld_language ) {
					unset($list[$i]['language']);
				}
				if ( !$this->fld_description ) {
					unset($list[$i]['description']);
				}
			}
			$this->result->setIndexedTagName($list, 'd');
			$result->addValue( array( 'query', 'pages', 123 ), 'pageid', 123, true );
			$result->addValue( array( 'query', 'pages', 123 ), 'ns', 0, true );
			$result->addValue( array( 'query', 'pages', 123 ), 'title', 'q7', true );
			$this->addItemDescriptions( $result, 123, $list );
		}
		$this->result->setIndexedTagName_internal( array( 'query', 'pages' ), 'page' );
	}

	/**
	 * Add page descriptions to an ApiResult, adding a continue
	 * parameter if it doesn't fit.
	 *
	 * @param $result ApiResult
	 * @param $page int
	 * @param $descriptions array
	 * @return bool True if it fits in the result
	 */
	private function addItemDescriptions( $result, $page, $descriptions ) {
		$fit = $result->addValue( array( 'query', 'pages', $page ), 'descriptions', $descriptions );

		if ( !$fit ) {
			$this->setContinueEnumParameter( 'continue', $page );
		}
		return $fit;
	}
	
	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		return array(
			'continue' => array(
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
			'test' => array(
			),
			'prop' => array(
				ApiBase::PARAM_DFLT => 'language|description',
				ApiBase::PARAM_TYPE => array(
					'language',
					'description'
				),
				ApiBase::PARAM_ISMULTI => true
			)
		);
	}

	public function getParamDescription() {
		return array(
			'continue' => 'When more results are available, use this to continue',
			'limit' => 'The maximum number of descriptions to list',
			'test' => 'Add some dummy data for testing purposes',
			'prop' => array(
				'Which properties to get',
				' language     - Adds the language for this description',
				' description  - Adds the actual string for this description',
			),
		);
	}

	public function getDescription() {
		return 'List item descriptions';
	}

	public function getExamples() {
		return array(
			'api.php?action=query&list=wbdescriptions&wbdprop=language|description'
				=> 'Get the list of descriptions for each of the Wikibase items',
			'api.php?action=query&list=wbdescriptions&wbdprop=language|description&wbdtest'
				=> 'Get the list of descriptions for each of the Wikibase items and add some dummydata for testing'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
