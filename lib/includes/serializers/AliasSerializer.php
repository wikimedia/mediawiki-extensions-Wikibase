<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for aliases.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class AliasSerializer extends SerializerObject implements Unserializer {

	/**
	 * Returns a serialized array of aliases.
	 *
	 * @since 0.4
	 *
	 * @param array $aliases
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final public function getSerialized( $aliases ) {
		if ( !is_array( $aliases ) ) {
			throw new InvalidArgumentException( 'AliasSerializer can only serialize an array of aliases' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$value = array();

		if ( !$this->options->shouldIndexTags() ) {
			foreach ( $aliases as $languageCode => $alarr ) {
				$arr = array();
				foreach ( $alarr as $alias ) {
					if ( $alias === '' ) {
						continue; // skip empty aliases
					}
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
					if ( $alias === '' ) {
						continue; // skip empty aliases
					}
					$value[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
			}
		}

		if ( $this->options->shouldIndexTags() ) {
			$this->setIndexedTagName( $value, 'alias' );
		}

		return $value;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function newFromSerialization( array $data ) {
		$aliases = array();

		foreach( $data as $key => $aliasSet ) {
			if ( $key === '_element' ) {
				continue;
			}

			if ( !is_array( $aliasSet ) ) {
				throw new InvalidArgumentException( 'Alias data is invalid.' );
			}

			$lang = array_key_exists( 'language', $aliasSet ) ? $aliasSet['language'] : $key;

			if ( array_key_exists( 'value', $aliasSet ) ) {
				$aliases[$lang][] = $aliasSet['value'];
			} else {
				$aliases[$lang] = $this->extractAliasValues( $aliasSet );
			}
		}

		return $aliases;
	}

	/**
	 * @param array $aliasSet
	 *
	 * @throws InvalidArgumentException
	 * @return string[]
	 */
	protected function extractAliasValues( array $aliasSet ) {
		$aliases = array();

		foreach( $aliasSet as $alias ) {
			if ( is_array( $alias ) && array_key_exists( 'value', $alias ) && is_string( $alias['value'] ) ) {
				$aliases[] = $alias['value'];
			} else {
				throw new InvalidArgumentException( 'Alias value is invalid' );
			}
		}

		return $aliases;
	}

}
