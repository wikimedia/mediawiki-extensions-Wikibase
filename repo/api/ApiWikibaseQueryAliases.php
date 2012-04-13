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
 * API module to get the list of aliases for a single Wikibase item.
 *
 * @since 0.1
 *
 * @file ApiWikibaseQueryAliases.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseQueryAliases extends ApiQueryBase {

	/**
	 * @var ApiResult
	 */
	private $result;

	private $limit;
	private $fld_language = false,
			$fld_site = false,
			$fld_alias = false;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'wba' );
	}

	public function execute() {
		$params = $this->extractRequestParams();

		$prop = array_flip( $params['prop'] );

		$this->fld_language = isset( $prop['language'] );
		$this->fld_site = isset( $prop['site'] );
		$this->fld_alias = isset( $prop['alias'] );

		$this->limit = $params['limit'];
		$this->result = $this->getResult();
		
		$aliases = array();
		
		// TODO: Stuff to be added later
		
		if ( count( $aliases ) ) {
			# Add any remaining properties to the results
			$this->addItemAliases( $result, $currentPage, $labels );
		}
	
		// If we are testing we add some dummy data
		// TODO: Remove this when we go into production
		if ( WBSettings::get( 'apiInTest' ) && isset($params['test']) ) {
			$list = array(
				array('language'=>'da', 'site'=>'wikipedia', 'alias'=>'Testing nummer'),
				array('language'=>'de', 'site'=>'wikipedia', 'alias'=>'Testen zahl'),
				array('language'=>'en', 'site'=>'wikipedia', 'alias'=>'Testing number'),
				array('language'=>'no', 'site'=>'wikipedia', 'alias'=>'Testing tall'),
				array('language'=>'nn', 'site'=>'wikipedia', 'alias'=>'Testing tal'),
				array('language'=>'sv', 'site'=>'wikipedia', 'alias'=>'Testing tal'),
			);
			for ($i=0, $l = count($list); $i<$l; $i++) {
				if ( !$this->fld_language ) {
					unset($list[$i]['language']);
				}
				if ( !$this->fld_site ) {
					unset($list[$i]['site']);
				}
				if ( !$this->fld_alias ) {
					unset($list[$i]['alias']);
				}
			}
			$this->result->setIndexedTagName($list, 'a');
			$this->result->addValue( array( 'query', 'pages', 123 ), 'pageid', 123, true );
			$this->result->addValue( array( 'query', 'pages', 123 ), 'ns', 0, true );
			$this->result->addValue( array( 'query', 'pages', 123 ), 'title', 'q7', true );
			$this->addItemAliases( $this->result, 123, $list );
		}
		$this->result->setIndexedTagName_internal( array( 'query', 'pages' ), 'page' );
	}

	/**
	 * Add page descriptions to an ApiResult, adding a continue
	 * parameter if it doesn't fit.
	 *
	 * @param $result ApiResult
	 * @param $page int
	 * @param $aliases array
	 * @return bool True if it fits in the result
	 */
	private function addItemAliases( $result, $page, $aliases ) {
		$fit = $result->addValue( array( 'query', 'pages', $page ), 'aliases', $aliases );

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
				ApiBase::PARAM_DFLT => 'language|site|alias',
				ApiBase::PARAM_TYPE => array(
					'language',
					'site',
					'alias'
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
				' language     - Adds the language for this label',
				' site         - Adds the site for this label',
				' alias        - Adds the actual string for this alias',
			),
		);
	}

	public function getDescription() {
		return 'List item labels';
	}

	public function getExamples() {
		return array(
			'api.php?action=query&list=wbaliases&wbaprop=language|site|alias'
				=> 'Get the list of aliases for each of the Wikibase items',
			'api.php?action=query&list=wbaliases&wbaprop=language|site|alias&wbatest'
				=> 'Get the list of aliases for each of the Wikibase items and add some dummydata for testing'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}