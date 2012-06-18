<?php

namespace Wikibase;
use User, Title, ApiBase, Sanitizer;

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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiModifyItem extends Api {

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
	protected abstract function modifyItem( Item &$item, array $params );
	
	/**
	 * Check the rights for the user accessing the module, that is a subclass of this one.
	 * 
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected abstract function getPermissionsErrorInternal( $user, array $params, $module=null, $op=null );

	/**
	 * Check the rights for the user accessing the module, module name and operation comes from the actual subclass.
	 * 
	 * @param $title Title object where the item is stored
	 * @param $user User doing the action
	 * @param $mod null|String name of the module, usually not set
	 * @param $op null|String operation that is about to be done, usually not set
	 * @return array of errors reported from the static getPermissionsError
	 */
	protected static function getPermissionsError( $user, $mod=null, $op=null ) {
		if ( Settings::get( 'apiInDebug' ) ? !Settings::get( 'apiDebugWithRights', false ) : false ) {
			return null;
		}
		
		return !$user->isAllowed( is_string($mod) ? "{$mod}-{$op}" : $op);
	}
	
	/**
	 * Make a string for an auto comment.
	 *
	 * @since 0.1
	 *
	 * @param $useShortMessage boolean that switch between long and short format
	 * @param $params array with parameters for the call
	 * @return string that can be used as an auto comment
	 */
	protected abstract function autoComment( array $params, $useShortMessage=false );

	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		// note that this is changed back and could fail
		if ( !( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) )
			&& !( isset( $params['item'] ) && $params['item'] === 'add' ) ) {

			$this->dieUsage( wfMsg( 'wikibase-api-id-xor-wikititle' ), 'id-xor-wikititle' );
		}

		if ( isset( $params['id'] ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-with-id' ), 'add-with-id' );
		}
	}

	/**
	 * Main method. Does the actual work and sets the result.
	 *
	 * @since 0.1
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$user = $this->getUser();

		$success = false;

		// This is really already done with needsToken()
		if ( $this->needsToken() && !$user->matchEditToken( $params['token'] ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-session-failure' ), 'session-failure' );
		}
		
		// FIXME: This does not check if user is blocked or page is protected
		if ( !$user->isAllowed( 'edit' ) ) {
			$this->dieUsageMsg( 'cantedit' );
		}

		$hasLink = isset( $params['site'] ) && $params['title'];
		$item = null;

		$this->validateParameters( $params );
		
		if ( $params['item'] === 'update' && !isset( $params['id'] ) && !$hasLink ) {
			$this->dieUsage( wfMsg( 'wikibase-api-update-without-id' ), 'update-without-id' );
		}

		if ( isset( $params['id'] ) ) {
			$item = Item::getFromId( $params['id'] );

			if ( is_null( $item ) ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-id' ), 'no-such-item-id' );
			}
		}
		elseif ( $hasLink ) {
			$item = Item::getFromSiteLink( $params['site'], Item::normalize( $params['title'] ) );
			
			if ( is_null( $item ) && $params['item'] === 'update' ) {
				$this->dieUsage( wfMsg( 'wikibase-api-no-such-item-link' ), 'no-such-item-link' );
			}
		}
		
		if ( !is_null( $item ) && !( $item instanceof Item ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-wrong-class' ), 'wrong-class' );
		}
			
		if ( !is_null( $item ) && $params['item'] === 'add' ) {
			$this->dieUsage( wfMsg( 'wikibase-api-add-exists' ), 'add-exists', 0, array( 'item' => array( 'id' => $params['id'] ) ) );
		}

		if ( is_null( $item ) ) {
			$item = Item::newEmpty();

			if ( $hasLink ) {
				$item->addSiteLink( $params['site'], $params['title'] );
			}
		}
		
		$this->setUsekeys( $params );
		$success = $this->modifyItem( $item, $params );
		if ( !$success ) {
			$this->dieUsage( wfMsg( 'wikibase-api-modify-failed' ), 'modify-failed' );
		}

		$isNew = $item->isNew();
		
		// TODO: Change for more fine grained permissions
		$user = $this->getUser();
		if ( $this->getPermissionsErrorInternal( $this->getUser(), $params ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-no-permissions' ), 'no-permissions' );
		}
		
		$summary = Sanitizer::escapeHtmlAllowEntities( isset($params['summary']) ? $params['summary'] : '' );
		$comment = $this->autoComment(
			$params,
			SUMMARY_MAX_LENGTH
				- strlen( $summary ) /* this must be counted for a filtered string, and its a byte count on a multibyte string */
				- (isset( $params['summary'] ) ? 6 : 7) /* add according to overall formatting */
		);
		$comment = $comment !== "" ? "/* $comment */" : '';
		$success = $item->save( $comment . ( ($comment !== "" && $summary !== "") ? '' : ' ' ) . $summary );

		if ( !$success ) {
			if ( $isNew ) {
				$this->dieUsage( wfMsg( 'wikibase-api-create-failed' ), 'create-failed' );
			}
			else {
				$this->dieUsage( wfMsg( 'wikibase-api-save-failed' ), 'save-failed' );
			}
		}

		if ( $success ) {
			$this->getResult()->addValue(
				'item',
				'id', $item->getId()
			);
			if ( $hasLink ) {
				// normalizing site does not really give any meaning
				// so we only normalize title
				$normTitle = Item::normalize( $params['title'] );
				$normalized = array();
				if ( $normTitle !== $params['title'] ) {
					$normalized['from'] = $params['title'];
					$normalized['to'] = $normTitle;
				}
				if ( count( $normalized ) ) {
					$this->getResult()->addValue(
						'item',
						'normalized', $normalized
					);
				}
			}
		}

		$this->getResult()->addValue(
			null,
			'success',
			(int)$success
		);
		
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'id-xor-wikititle', 'info' => wfMsg( 'wikibase-api-id-xor-wikititle' ) ),
			array( 'code' => 'add-with-id', 'info' => wfMsg( 'wikibase-api-add-with-id' ) ),
			array( 'code' => 'add-exists', 'info' => wfMsg( 'wikibase-api-add-exists' ) ),
			array( 'code' => 'update-without-id', 'info' => wfMsg( 'wikibase-api-update-without-id' ) ),
			array( 'code' => 'no-such-item-link', 'info' => wfMsg( 'wikibase-api-no-such-item-link' ) ),
			array( 'code' => 'no-such-item-id', 'info' => wfMsg( 'wikibase-api-no-such-item-id' ) ),
			array( 'code' => 'create-failed', 'info' => wfMsg( 'wikibase-api-create-failed' ) ),
			array( 'code' => 'modify-failed', 'info' => wfMsg( 'wikibase-api-modify-failed' ) ),
			array( 'code' => 'wrong-class', 'info' => wfMsg( 'wikibase-api-wrong-class' ) ),
			array( 'code' => 'save-failed', 'info' => wfMsg( 'wikibase-api-save-failed' ) ),
			array( 'code' => 'invalid-contentmodel', 'info' => wfMsg( 'wikibase-api-invalid-contentmodel' ) ),
			array( 'code' => 'no-permissions', 'info' => wfMsg( 'wikibase-api-no-permissions' ) ),
			array( 'code' => 'session-failure', 'info' => wfMsg( 'wikibase-api-session-failure' ) ),
			) );
	}

	/**
	 * Returns whether this module requires a Token to execute
	 * @return bool
	 */
	public function needsToken() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithTokens' ) : true ;
	}

	/**
	 * Indicates whether this module must be called with a POST request
	 * @return bool
	 */
	public function mustBePosted() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithPost' ) : true ;
	}

	/**
	 * Indicates whether this module requires write mode
	 * @return bool
	 */
	public function isWriteMode() {
		return Settings::get( 'apiInDebug' ) ? Settings::get( 'apiDebugWithWrite' ) : true ;
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
			//'create' => array(
			//	ApiBase::PARAM_TYPE => 'boolean',
			//),
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'site' => array(
				ApiBase::PARAM_TYPE => Sites::singleton()->getIdentifiers(),
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'summary' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'item' => array(
				ApiBase::PARAM_TYPE => array( 'add', 'update', 'set' ),
				ApiBase::PARAM_DFLT => 'update',
			),
			'token' => null,
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
			'id' => array( 'The ID of the item.',
				"Use either 'id' or 'site' and 'title' together."
			),
			'site' => array( 'An identifier for the site on which the page resides.',
				"Use together with 'title'."
			),
			'title' => array( 'Title of the page to associate.',
				"Use together with 'site'."
			),
			'item' => array( 'Indicates if you are changing the content of the item.',
				"add - the item should not exist before the call or an error will be reported.",
				"update - the item shuld exist before the call or an error will be reported.",
				"set - the item could exist or not before the call.",
			),
			'summary' => array( 'Summary for the edit.',
				"Will be prepended by an automatically generated comment."
			),
			'token' => 'A "setitem" token previously obtained through the gettoken parameter', // or prop=info,
		) );
	}

	/**
	 * Pick values from the params array and string them together
	 *
	 * @since 0.1
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $max_length integer is the byte length of the available space
	 */
	protected static function pickValuesFromParams( array $params, $max_length ) {

		// the keys of the values that shall be extracted
		$keys = func_get_args();

		// shift out the args that is handled
		array_shift( $keys );
		array_shift( $keys );

		// accumulator for the length
		$overallLength = 0;

		// the continuation marker can't be localized as it is written into the comment
		$contLength = strlen( SUMMARY_CONTINUATION );

		// check if there is any space at all
		if ( $max_length <= 0 ) {
			return '';
		}

		// collect the values
		// note that we must figure out how many of the args we can find and what they contains
		// before we can chop the remaining string to the correct length
		$arr = array();
		foreach ( array_intersect_key( $params, array_flip( $keys ) ) as $k => $v ) {
			if ( is_string( $v ) ) {
				$value = Sanitizer::escapeHtmlAllowEntities( $v );
				// note that we use previous overall length and fix overflow later
				if ( $max_length < $overallLength ) {
					array_push( $arr, $continuationMarker );
					$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
				}
				else {
					array_push( $arr, $value );
					$overallLength += ( $overallLength ? 1 + strlen( $value ) : strlen( $value ) );
				}
			}
			elseif ( is_array( $v ) ) {
				$value = Sanitizer::escapeHtmlAllowEntities( join( SUMMARY_SUBGROUPING, $v ) );
				// note that we use previous overall length and fix overflow later
				if ( $max_length < $overallLength ) {
					array_push( $arr, $continuationMarker );
					$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
				}
				else{
					array_push( $arr, $value );
					$overallLength += ( $overallLength ? 1 + strlen( $value ) : strlen( $value ) );
				}
			}
			else {
				array_push( $arr, $continuationMarker );
				$overallLength += ( $overallLength ? 1 + $contLength : $contLength );
			}
		}
		// if the overall length of the collected string is to long the values needs to be shortened
		// that is the elements with overflow from the previous must be identified and shortened
		if ( $overallLength > $max_length ) {
			for ( $i = count( $arr )-1; 0 <= $i; $i-- ) {
				// can't shorten?
				if ( $arr[$i] === "" || $arr[$i] === $continuationMarker ) {
					continue;
				}
				// still too long?
				elseif ( $overallLength > $max_length ) {
					$lengthOfElement = strlen( $arr[$i] );
					$diffLength = $max_length - $overallLength;
					// to little space left?
					if ($diffLength < 0) {
						$diffLength -= $contLength;
						$matchLength = $lengthOfElement + $diffLength;
						$chopped = mb_strcut( $arr[$i], 0, $matchLength );
						$chopped .= SUMMARY_CONTINUATION;
						$overallLength += strlen( $chopped ) - $lengthOfElement;
						$arr[$i] = $chopped;
					}
				}
			}
		}
		return implode('|', $arr);
	}

}
