<?php

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file ApiWikibaseModifyItem.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiWikibaseSetItem extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$success = false;
		
		// lacks error checking
		$item = WikibaseItem::newFromArray( json_decode( $params['data'] ) );
		$success = $item->save();

		if ( !$success ) {
			// TODO: throw error. Right now will have PHP fatal when accessing $item later on...
		}

		if ( !isset($params['summary']) ) {
			//$params['summary'] = $item->getTextForSummary();
			$params['summary'] = 'dummy';
		}

		$languages = WikibaseUtils::getLanguageCodes();
		
		// because this is serialized and cleansed we can simply go for known values
		$this->getResult()->addValue(
			NULL,
			'item',
			array(
				'id' => $item->getId(),
				'sitelinks' => $item->getSiteLinks(),
				'descriptions' => $item->getDescriptions($languages),
				'labels' => $item->getLabels($languages)
			)
		);
		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
	}
	
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );
	}

	public function needsToken() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function mustBePosted() {
		return !WBSettings::get( 'apiInDebug' );
	}

	public function isWriteMode() {
		return !WBSettings::get( 'apiInDebug' );
	}
	
	public function getAllowedParams() {
		return array(
			'data' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			),
		);
	}

	public function getParamDescription() {
		return array(
			'data' => array( 'The serialized object that is used as the data source.',
				"The newly created item will be assigned an item 'id'."
			),
		);
	}

	public function getDescription() {
		return array(
			'API module to create a new Wikibase item and modify it with serialised information.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=wbsetitem&data={}'
			=> 'Set an empty JSON structure for the item, it will be extended with an item id and the structure cleansed and completed',
			'api.php?action=wbsetitem&data={"label":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Set a more complete JSON structure for the item.',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetitem';
	}


	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
	
}