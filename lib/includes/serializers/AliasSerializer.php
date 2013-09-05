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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class AliasSerializer extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.4
	 *
	 * @var MultiLangSerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param MultiLangSerializationOptions $options
	 */
	public function __construct( MultiLangSerializationOptions $options = null ) {
		if ( $options === null ) {
			$this->options = new MultiLangSerializationOptions();
		}
		parent::__construct( $options );
	}

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
	public final function getSerialized( $aliases ) {
		if ( !is_array( $aliases ) ) {
			throw new InvalidArgumentException( 'AliasSerializer can only serialize an array of aliases' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$value = array();

		if ( $this->options->shouldUseKeys() ) {
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

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'alias' );
		}

		return $value;
	}
}
