<?php

namespace Wikibase\Repo;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Serializers\Exceptions\SerializationException;
use Serializers\Serializer;
use Wikibase\DataModel\Statement\Statement;

/**
 * Serializer and Deserializer for Statement Ranks.
 *
 * @todo this could be moved to DataModelSerialization (in some form)
 *
 * @license GPL-2.0-or-later
 */
class StatementRankSerializer implements Serializer, Deserializer {

	private const RANK_MAP = [
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred',
	];

	/**
	 * Returns the available ranks in serialized form.
	 *
	 * @return string[]
	 */
	public static function getRanks() {
		return array_values( self::RANK_MAP );
	}

	/**
	 * Deserializes the rank and returns an element from the Statement::RANK_ enum.
	 *
	 * @param string $serializedRank
	 *
	 * @throws DeserializationException
	 * @return int
	 * @suppress PhanParamSignatureMismatch
	 */
	public function deserialize( $serializedRank ) {
		$ranks = array_flip( self::RANK_MAP );

		if ( !array_key_exists( $serializedRank, $ranks ) ) {
			throw new DeserializationException( 'Invalid rank serialization' );
		}

		return $ranks[$serializedRank];
	}

	/**
	 * Serializes the rank.
	 *
	 * @param int $rank
	 *
	 * @throws SerializationException
	 * @return string
	 */
	public function serialize( $rank ) {
		if ( !array_key_exists( $rank, self::RANK_MAP ) ) {
			throw new SerializationException( 'Invalid rank' );
		}

		return self::RANK_MAP[$rank];
	}

}
