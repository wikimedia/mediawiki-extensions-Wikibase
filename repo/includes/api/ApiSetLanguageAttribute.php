<?php

namespace Wikibase;
use ApiBase;

/**
 * API module to set the language attributes for a Wikibase item.
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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ApiSetLanguageAttribute extends ApiModifyItem {

	/**
	 * Check the rights for the user accessing this module.
	 * This is called from ModifyItem.
	 *
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected function getPermissionsErrorInternal( $user, array $params, $mod=null, $op=null ) {
		return parent::getPermissionsError( $user, 'lang-attr', $params['item'] );
	}
	
	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );
		
		if ( !isset( $params['label'] ) && !isset( $params['description'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-label-or-description' ), 'label-or-description' );
		}
	}

	/**
	 * Make a string for an auto comment.
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $available integer the number of bytes available for the autocomment
	 * @return string that can be used as an auto comment
	 */
	protected function autoComment( array $params, $available=128 ) {
		$count = (int)isset( $params['label'] ) + (int)isset( $params['description'] ) + (int)isset( $params['badges'] );
		if ( 1<$count ) {
			$comment = "set-language-attributes:" . $params['language']
				. SUMMARY_GROUPING
				. ApiModifyItem::pickValuesFromParams( $params, $available - strlen( "set-language-attributes:" . $params['language'] ), 'badge', 'label', 'description' );
		}
		elseif ( isset( $params['label'] ) ) {
			$comment = "set-language-label:" . $params['language']
				. SUMMARY_GROUPING
				. ApiModifyItem::pickValuesFromParams( $params, $available - strlen( "set-language-label:" . $params['language'] ), 'label');
		}
		elseif ( isset( $params['description'] ) ) {
			$comment = "set-language-description:" . $params['language']
				. SUMMARY_GROUPING
				. ApiModifyItem::pickValuesFromParams( $params, $available - strlen( "set-language-description:" . $params['language'] ), 'description' );
		}
		elseif ( isset( $params['badges'] ) ) {
			$comment = "set-language-badges:" . $params['language']
				. SUMMARY_GROUPING
				. ApiModifyItem::pickValuesFromParams( $params, $available - strlen( "set-language-badges:" . $params['language'] ), 'badges' );
		}
		else {
			$comment = '';
		}
		return $comment;
	}
	
	/**
	 * Actually modify the item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param array $params
	 *
	 * @return boolean Success indicator
	 */
	protected function modifyItem( Item &$item, array $params ) {
		$language = $params['language'];

		if ( isset( $params['label'] ) ) {
			$labels = array( $language => $item->setLabel( $language, $params['label'] ) );
			$this->addLabelsToResult( $labels, 'item' );
		}

		if ( isset( $params['description'] ) ) {
			$descriptions = array( $language => $item->setDescription( $language, $params['description'] ) );
			$this->addDescriptionsToResult( $descriptions, 'item' );
		}

		//if ( isset( $params['badges'] ) ) {
		//	$badges = array( $language => $item->setBadges( $language, $params['badges'] ) );
		//	$this->addBadgesToResult( $badges, 'item' );
		//}

		// Because we can't fail?
		$success = true;
		
		return $success;
	}
	
	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'language' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
				ApiBase::PARAM_REQUIRED => true,
			),
			'label' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'description' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'badges' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
		) );
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'language' => 'Language for the label and description',
			'label' => 'The value to set for the label',
			'description' => 'The value to set for the description',
			'badges' => 'The values to set for the badges',
		) );
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getDescription() {
		return array(
			'API module to set a label or description for a single Wikibase item.'
		);
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'label-or-description', 'info' => wfMsg( 'wikibase-api-label-or-description' ) ),
			) );
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia&format=jsonfm'
				=> 'Set the string "Wikimedia" for page with id "42" as a label in English language and report it as pretty printed json',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "An encyclopedia that everyone can edit" for page with id "42" as a decription in English language',
			'api.php?action=wbsetlanguageattribute&id=42&language=en&label=Wikimedia&description=An%20encyclopedia%20that%20everyone%20can%20edit'
				=> 'Set the string "Wikimedia" for page with id "42" as a label, and the string "An encyclopedia that everyone can edit" as a decription in English language',
		);
	}
	
	/**
	 * @return bool|string|array Returns a false if the module has no help url, else returns a (array of) string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Wikibase/API#wbsetlanguageattribute';
	}

	/**
	 * Returns a string that identifies the version of this class.
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
