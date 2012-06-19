<?php

namespace Wikibase;
use UtfNormal, User, Status;

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
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array(
			'usekeys' => 'Use the keys in formats that supports them, otherwise fall back to the ordinary style',
		);
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array(
			'usekeys' => array(
				\ApiBase::PARAM_TYPE => 'boolean',
			),
		);
	}

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

	protected function addSiteLinksToResult( array $siteLinks, $path, $name = 'sitelinks', $tag = 'sitelink' ) {
		$value = array();
		$idx = 0;

		foreach ( $siteLinks as $siteId => $pageTitle ) {
			$value[$this->usekeys ? $siteId : $idx++] = array(
				'site' => $siteId,
				'title' => $pageTitle,
			);
		}

		if ( $value !== array() ) {
			if (!$this->usekeys) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	protected function addDescriptionsToResult( array $descriptions, $path, $name = 'descriptions', $tag = 'description' ) {
		$value = array();
		$idx = 0;

		foreach ( $descriptions as $languageCode => $description ) {
			$value[$this->usekeys ? $languageCode : $idx++] = array(
				'language' => $languageCode,
				'value' => $description,
			);
		}

		if ( $value !== array() ) {
			if (!$this->usekeys) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	protected function addLabelsToResult( array $labels, $path, $name = 'labels', $tag = 'label' ) {
		$value = array();
		$idx = 0;

		foreach ( $labels as $languageCode => $label ) {
			$value[$this->usekeys ? $languageCode : $idx++] = array(
				'language' => $languageCode,
				'value' => $label,
			);
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

	/**
	 * Trim initial and trailing whitespace, and compress internal ones.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 * @return filtered string where whitespace possibly are removed.
	 */
	static public function squashWhitespace( $inputString ) {
		return preg_replace( '/(\s+)/', ' ', preg_replace( '/(^\s+|\s+$)/', '', $inputString ) );
	}

	/**
	 * Normalize string into NFC after first checkingh if its already normalized.
	 *
	 * @since 0.1
	 *
	 * @param string $inputString The actual string to process.
	 * @return filtered string where whitespace possibly are removed.
	 */
	static public function conditionalToNFC( $inputString ) {
		// Note that quickIsNFCVerify will do some cleanup of the string,
		// but if we fail to detect a legal string, then we convert
		// the filtered string anyhow.
		if ( !UtfNormal::quickIsNFCVerify( $inputString ) ) {
			return UtfNormal::toNFC( $inputString );
		}
		return $inputString;
	}

	/**
	 * Do a toNFC after the string is squashed
	 *
	 * @since 0.1
	 *
	 * @param string $inputString
	 * @return trimmed string on NFC form
	 */
	static public function squashToNFC( $inputString ) {
		return self::conditionalToNFC( self::squashWhitespace( $inputString ) );
	}

}
