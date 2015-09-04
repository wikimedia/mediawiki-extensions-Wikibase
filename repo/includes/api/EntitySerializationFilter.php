<?php

namespace Wikibase\Repo\Api;

/**
 * Filter for entity serializations
 *
 * @licence GNU GPL v2+
 */
class EntitySerializationFilter {

	/**
	 * @var string[]
	 */
	private $props;

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
	 * @param $props string[]
	 */
	public function setProps( array $props ) {
		$this->props = $props;
	}

	public function getProps() {
		return $this->props;
	}

	/**
	 * Set the site filter.
	 *
	 * @param $siteIds string[]
	 */
	public function setSiteIds( array $siteIds ) {
		$this->siteIds = $siteIds;
	}

	/**
	 * Set the lang filter.
	 *
	 * @param $langCodes string[]
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
		if ( is_array( $this->props ) ) {
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
