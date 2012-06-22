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

}