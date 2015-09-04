<?php

namespace Wikibase\Repo\Api;

use Wikimedia\Assert\Assert;

/**
 * Filter for entity serializations
 *
 * @licence GNU GPL v2+
 */
class EntitySerializationFilter {

	/**
	 * @var string|string[]
	 */
	private $props = 'all';

	/**
	 * @var string[]
	 */
	private $siteIds;

	/**
	 * @var string[]
	 */
	private $langCodes;

	/**
	 * Set the props filter.
	 *
	 * @param string|string[] $props
	 */
	public function setProps( $props ) {
		Assert::parameter(
			is_array( $props ) || $props === 'all',
			'$props',
			'$props must be an array or "all"'
		);
		$this->props = $props;
	}

	/**
	 * @return string|string[]
	 */
	public function getProps() {
		return $this->props;
	}

	/**
	 * Set the site filter.
	 *
	 * @param string[] $siteIds
	 */
	public function setSiteIds( array $siteIds ) {
		$this->siteIds = $siteIds;
	}

	/**
	 * Set the lang filter.
	 *
	 * @param string[] $langCodes
	 */
	public function setLangCodes( array $langCodes ) {
		$this->langCodes = $langCodes;
	}

	/**
	 * @param array $serialization
	 *
	 * @return array
	 */
	public function filterByProps( array $serialization ) {
		if ( $this->props !== 'all' ) {
			if ( !in_array( 'labels', $this->props ) ) {
				unset( $serialization['labels'] );
			}
			if ( !in_array( 'descriptions', $this->props ) ) {
				unset( $serialization['descriptions'] );
			}
			if ( !in_array( 'aliases', $this->props ) ) {
				unset( $serialization['aliases'] );
			}
			if ( !in_array( 'claims', $this->props ) ) {
				unset( $serialization['claims'] );
			}
			if ( !in_array( 'sitelinks', $this->props ) ) {
				unset( $serialization['sitelinks'] );
			}
		}
		return $serialization;
	}

	/**
	 * @param array $serialization
	 *
	 * @return array
	 */
	public function filterBySiteIds( array $serialization ) {
		if ( !empty( $this->siteIds ) && array_key_exists( 'sitelinks', $serialization ) ) {
			foreach ( $serialization['sitelinks'] as $siteId => $siteLink ) {
				if ( is_array( $siteLink ) && !in_array( $siteLink['site'], $this->siteIds ) ) {
					unset( $serialization['sitelinks'][$siteId] );
				}
			}
		}
		return $serialization;
	}

	/**
	 * @param array $serialization
	 *
	 * @return array
	 */
	public function filterByLangCodes( array $serialization ) {
		if ( !empty( $this->langCodes ) ) {
			if ( array_key_exists( 'labels', $serialization ) ) {
				foreach ( $serialization['labels'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->langCodes ) ) {
						unset( $serialization['labels'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'descriptions', $serialization ) ) {
				foreach ( $serialization['descriptions'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->langCodes ) ) {
						unset( $serialization['descriptions'][$langCode] );
					}
				}
			}
			if ( array_key_exists( 'aliases', $serialization ) ) {
				foreach ( $serialization['aliases'] as $langCode => $languageArray ) {
					if ( !in_array( $langCode, $this->langCodes ) ) {
						unset( $serialization['aliases'][$langCode] );
					}
				}
			}
		}
		return $serialization;
	}

}
