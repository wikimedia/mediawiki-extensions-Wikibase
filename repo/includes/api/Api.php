<?php

namespace Wikibase;
use User, Status, ApiBase;

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file ApiWikibase.php
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class Api extends \ApiBase {
	/**
	 * Var to keep the set status for later use
	 * @var bool how to handle the keys
	 */
	protected $usekeys = false;
	
	/**
	 * Sets the usekeys-state for later use (and misuse)
	 *
	 * @param $params array parameters requested in subclass
	 */
	protected function setUsekeys( array $params ) {
		$usekeys = Settings::get( 'apiUseKeys' ) || ( isset( $params['usekeys'] ) ? $params['usekeys'] : false );

		if ( $usekeys ) {
			$format = $this->getMain()->getRequest()->getVal( 'format' );
			$withkeys = Settings::get( 'formatsWithKeys' );
			$usekeys = isset( $format ) && isset( $withkeys[$format] ) ? $withkeys[$format] : false;
		}

		$this->usekeys = $usekeys;
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'jsonp-token-violation', 'info' => wfMsg( 'wikibase-api-jsonp-token-violation' ) ),
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		$descriptions = array(
			'gettoken' => array( 'If set, a new "modifyitem" token will be returned if the request completes.',
				'The remaining of the call must be valid, otherwise an error can be returned without the token included.'
			),
		);
		if ( Settings::get( 'apiUseKeys' ) ) {
			$descriptions['nousekeys'] = array( 'Turn off use the keys. The use of keys are only used in formats that supports them,',
				'otherwise fall back to the ordinary style which is to use keys.'
			);
		}
		else {
			$descriptions['usekeys'] = array( 'Turn on use the keys. The use of keys are only used in formats that supports them,',
				'otherwise fall back to the ordinary style which is to use keys.'
			);
		}
		return $descriptions;
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		$allowedParams = array(
			'gettoken' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
			'urls' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
		);
		if ( Settings::get( 'apiUseKeys' ) ) {
			$allowedParams['nousekeys'] = array( \ApiBase::PARAM_TYPE => 'boolean' );
		}
		else {
			$allowedParams['usekeys'] = array( \ApiBase::PARAM_TYPE => 'boolean' );
		}
		return $allowedParams;
	}

	/**
	 * Add token to result
	 *
	 * @since 0.1
	 *
	 * @param string $token new token to use
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 *
	 * @return array|bool
	 */
	protected function addTokenToResult( $token, $path=null, $name = 'itemtoken' ) {
		// in JSON callback mode, no tokens should be returned
		// this will then block later updates through reuse of cached scripts
		if ( !is_null( $this->getMain()->getRequest()->getVal( 'callback' ) ) ) {
			$this->dieUsage( wfMsg( 'wikibase-api-jsonp-token-violation' ), 'jsonp-token-violation' );
		}
		if ( is_null( $path ) ) {
			$path = array( null, $this->getModuleName() );
		}
		else if ( !is_array( $path ) ) {
			$path = array( null, (string)$path );
		}
		$path = is_null( $path ) ? $path : $this->getModuleName();
		$this->getResult()->addValue( $path, $name, $token );
	}

	/**
	 * Add aliases to result
	 *
	 * @since 0.1
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addAliasesToResult( array $aliases, $path, $name = 'aliases', $tag = 'alias' ) {
		$value = array();

		if ( $this->usekeys ) {
			foreach ( $aliases as $languageCode => $alarr ) {
				$arr = array();
				foreach ( $alarr as $alias ) {
					$arr[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
				$value[$languageCode] = $arr;
			}
		}
		else {
			foreach ( $aliases as $languageCode => $alarr ) {
				foreach ( $alarr as $alias ) {
					$value[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
			}
		}

		if ( $value !== array() ) {
			if (!$this->usekeys) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add sitelinks to result
	 *
	 * @since 0.1
	 *
	 * @param array $siteLinks the site links to insert in the result, as SiteLink objects
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 * @param array $options additional information to include in the listelinks structure. For example:
	 *              * 'url' will include the full URL of the sitelink in the result
	 *              * 'removed' will mark the sitelinks as removed
	 *              * other options will simply be included as flags.
	 *
	 * @return array|bool
	 */
	protected function addSiteLinksToResult( array $siteLinks, $path, $name = 'sitelinks', $tag = 'sitelink', $options = null ) {
		$value = array();
		$idx = 0;

		foreach ( $siteLinks as $link ) { /* @var SiteLink $link */
			$response = array(
				'site' => $link->getSiteID(),
				'title' => $link->getPage(),
			);

			if ( $options ) {
				foreach ( $options as $opt ) {
					if ( isset( $response[$opt] ) ) {
						//skip
					} elseif ( $opt === 'url' ) {
						//include full url in the result
						$response['url'] = $link->getUrl();
					} else {
						//include some flag in the result
						$response[$opt] = '';
					}
				}
			}

			$key = $this->usekeys ? $link->getSiteID() : $idx++;
			$value[ $key ] = $response;
		}

		if ( $value !== array() ) {
			if ( !$this->usekeys ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add descriptions to result
	 *
	 * @since 0.1
	 *
	 * @param array $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addDescriptionsToResult( array $descriptions, $path, $name = 'descriptions', $tag = 'description' ) {
		$value = array();
		$idx = 0;

		foreach ( $descriptions as $languageCode => $description ) {
			if ( $description === '' ) {
				$value[$this->usekeys ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->usekeys ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $description,
				);
			}
		}

		if ( $value !== array() ) {
			if (!$this->usekeys) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Add labels to result
	 *
	 * @since 0.1
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @return array|bool
	 */
	protected function addLabelsToResult( array $labels, $path, $name = 'labels', $tag = 'label' ) {
		$value = array();
		$idx = 0;

		foreach ( $labels as $languageCode => $label ) {
			if ( $label === '' ) {
				$value[$this->usekeys ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->usekeys ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $label,
				);
			}
		}

		if ( $value !== array() ) {
			if (!$this->usekeys) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Returns the permissions that are required to perform the operation specified by
	 * the parameters.
	 *
	 * @param $item Item the item to check permissions for
	 * @param $params array of arguments for the module, describing the operation to be performed
	 *
	 * @return \Status the check's result
	 */
	protected function getRequiredPermissions( Item $item, array $params ) {
		$permissions = array( 'read' );

		#could directly check for each module here:
		#$modulePermission = $this->getModuleName();
		#$permissions[] = $modulePermission;

		return $permissions;
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param $item ItemContent the item to check
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 *
	 * @return Status the check's result
	 * @todo: use this also to check for read access in ApiGetItems, etc
	 */
	public function checkPermissions( ItemContent $itemContent, User $user, array $params ) {
		if ( Settings::get( 'apiInDebug' ) && !Settings::get( 'apiDebugWithRights', false ) ) {
			return Status::newGood();
		}

		$permissions = $this->getRequiredPermissions( $itemContent->getItem(), $params );
		$status = Status::newGood();

		foreach ( $permissions as $perm ) {
			$permStatus = $itemContent->checkPermission( $perm, $user, true );
			$status->merge( $permStatus );
		}

		return $status;
	}

}