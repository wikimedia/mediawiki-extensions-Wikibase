<?php

/**
 * API module to set a description for a Wikibase item.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file ApiWikibaseSetDescription.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiWikibaseSetDescription extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * @var ApiResult
	 */
	private $result;
	
	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		
		$this->result = $this->getResult();
		
		// TODO: implement
		
		// If we are testing we add some dummy data
		// TODO: Remove this when we go into production
		if ( WBSettings::get( 'apiInTest' ) && isset($params['test']) ) {
			$this->result->addValue( array( 'wbsetdescription' ), 'result', 'Success', true );
			$this->result->addValue( array( 'wbsetdescription' ), 'pageid', 12, true );
			$this->result->addValue( array( 'wbsetdescription' ), 'title', 'q7', true );
			$this->result->addValue( array( 'wbsetdescription' ), 'oldrevid', 123, true );
			$this->result->addValue( array( 'wbsetdescription' ), 'newrevid', 456, true );
		}
	}

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
			),
			'language' => array(
				ApiBase::PARAM_TYPE => WikibaseUtils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'description' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
			'test' => array( // TODO: Remove this when we go into production
			),
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the item to set a description for',
			'description' => 'Language the description is in',
			'label' => 'The value to set for the description',
			'test' => 'Add some dummy data for testing purposes', // TODO: Remove this when we go into production
		);
	}

	public function getDescription() {
		return array(
			'API module to set a description for a Wikibase item.'
		);
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsetdescription&id=42&language=en&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" for page with id "42" as a decription in English language',
			'api.php?action=wbsetdescription&id=42&language=en&description=An%20encyclopedia%20that%20everyone%20can%20edit&test'
				=> 'Fake a set description, always returns the same values',
		);
	}
	
   	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikidata/API#wbsetdescription';
	}
	

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
