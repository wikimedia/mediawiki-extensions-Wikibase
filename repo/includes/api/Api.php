<?php

namespace Wikibase;
use User, Status, ApiBase;

/**
 * Base class for API modules modifying a single item identified based on id xor a combination of site and page title.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
abstract class Api extends \ApiBase {

	/**
	 * Figure out the overall usekeys-state
	 *
	 * @return bool true if the keys should be present
	 */
	public static function usekeys( $format ) {
		static $withkeys = false;
		if ( $withkeys === false ) {
			// Which formats to inject keys into, undefined entries are interpreted as true
			// TODO: This array must be patched if awailable formats that does NOT support
			// usekeys are added, changed or removed.
			$withkeys = array(
				'wddx' => false,
				'wddxfm' => false,
				'xml' => false,
				'xmlfm' => false,
			);
		}
		return ( isset( $withkeys[$format] ) ? $withkeys[$format] : true );
	}

	/**
	 * Figure out the instance-specific usekeys-state
	 *
	 * @return bool true if the keys should be present
	 */
	protected function getUsekeys() {
		$format = $this->getMain()->getRequest()->getVal( 'format' );
		return Api::usekeys( isset( $format ) ? $format : 'xml' );
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'jsonp-token-violation', 'info' => $this->msg( 'wikibase-api-jsonp-token-violation' )->text() ),
		);
	}

	/**
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array(
			'gettoken' => array( 'If set, a new "modifyitem" token will be returned if the request completes.',
				'The remaining of the call must be valid, otherwise an error can be returned without the token included.'
			),
		);
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
		return array(
			'gettoken' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
		);
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
			$this->dieUsage( $this->msg( 'wikibase-api-jsonp-token-violation' )->text(), 'jsonp-token-violation' );
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

		if ( $this->getUsekeys() ) {
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
			if ( !$this->getUsekeys() ) {
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

		if ( isset( $options ) ) {
			// figure out if the entries shall be sorted
			$dir = null;
			if ( in_array( 'ascending', $options ) ) {
				$dir = 'ascending';
			}
			elseif ( in_array( 'descending', $options ) ) {
				$dir = 'descending';
			}
			if ( isset( $dir ) ) {
				// Sort the sitelinks according to their global id
				$saftyCopy = $siteLinks; // keep a shallow copy;
				if ( $dir === 'ascending' ) {
					$sortOk = usort(
						$siteLinks,
						function( $a, $b ) {
							return strcmp( $a->getSite()->getGlobalId(), $b->getSite()->getGlobalId() );
						}
					);
				} elseif ( $dir === 'descending' ) {
					$sortOk = usort(
						$siteLinks,
						function( $a, $b ) {
							return strcmp( $b->getSite()->getGlobalId(), $a->getSite()->getGlobalId() );
						}
					);
				}
				if ( !$sortOk ) {
					$siteLinks = $saftyCopy;
				}
			}
		}

		/**
		 * @var SiteLink $link
		 */
		foreach ( $siteLinks as $link ) {
			$response = array(
				'site' => $link->getSite()->getGlobalId(),
				'title' => $link->getPage(),
			);

			if ( $options !== null ) {
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

			$key = $this->getUsekeys() ? $link->getSite()->getGlobalId() : $idx++;
			$value[$key] = $response;
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
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
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $description,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
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
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->getUsekeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $label,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->getUsekeys() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Returns the permissions that are required to perform the operation specified by
	 * the parameters.
	 *
	 * @param $entity Entity the entity to check permissions for
	 * @param $params array of arguments for the module, describing the operation to be performed
	 *
	 * @return \Status the check's result
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = array( 'read' );

		#could directly check for each module here:
		#$modulePermission = $this->getModuleName();
		#$permissions[] = $modulePermission;

		return $permissions;
	}

	/**
	 * Check the rights for the user accessing the module.
	 *
	 * @param $entityContent EntityContent the entity to check
	 * @param $user User doing the action
	 * @param $params array of arguments for the module, passed for ModifyItem
	 *
	 * @return Status the check's result
	 * @todo: use this also to check for read access in ApiGetItems, etc
	 */
	public function checkPermissions( EntityContent $entityContent, User $user, array $params ) {
		if ( Settings::get( 'apiInDebug' ) && !Settings::get( 'apiDebugWithRights', false ) ) {
			return Status::newGood();
		}

		$permissions = $this->getRequiredPermissions( $entityContent->getEntity(), $params );
		$status = Status::newGood();

		foreach ( $permissions as $perm ) {
			$permStatus = $entityContent->checkPermission( $perm, $user, true );
			$status->merge( $permStatus );
		}

		return $status;
	}

	/**
	 * Returns the list of sites that is suitable as a sitelink target.
	 *
	 * @return \SiteList
	 */
	protected function getSiteLinkTargetSites() {
		$group = Settings::get( 'siteLinkGroup' );
		return \Sites::singleton()->getSiteGroup( $group );
	}

}
