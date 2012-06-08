<?php

namespace Wikibase;

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
	 * Check the rights for the user accessing the module, that is a subclass of this one.
	 * 
	 * @param $params array of arguments for the module, passed for ModifyItem
	 * @param $arr array some value to be possibly stripped for keys
	 * @param $tag string to be used as a tag name for indexed elements
	 * @return array of key-valuepairs or only values
	 */
	protected function stripKeys( array $params, array $arr, $tag, $level=1 ) {
		if ( \WBSettings::get( 'apiUseKeys' ) ) {
			$usekeys = true;
		}
		else {
			$usekeys = $params['usekeys'];
		}
		if ( $usekeys ) {
			switch ( $this->getMain()->getRequest()->getVal( 'format' ) ) {
				case 'json':
				case 'jsonfm':
					$usekeys = true;
					break;
				case 'yaml':
				case 'yamlfm':
					$usekeys = true;
					break;
				case 'raw':
				case 'rawfm':
					$usekeys = true;
					break;
				default:
					$usekeys = false;
					break;
			}
		}
		if (!$usekeys) {
			$newarr = array();
			foreach ( array_values( $arr ) as $a ) {
				switch ($level) {
				case 2:
					if ( is_array( $a )) {
						$newarr = array_merge( $newarr, array_values( $a ) );
					}
					else {
						$newarr[] = $a;
					}
					break;
				case 1:
					$newarr[] = $a;
					break;
				}
			};
			
			if ( count( $newarr ) ) {
				$this->getResult()->setIndexedTagName( $newarr, $tag );
			}
			
			$arr = $newarr;
		}
		return $arr;
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
		
}