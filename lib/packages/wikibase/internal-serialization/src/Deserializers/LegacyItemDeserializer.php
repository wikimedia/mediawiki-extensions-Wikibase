<?php

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LegacyItemDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $idDeserializer;

	/**
	 * @var Deserializer
	 */
	private $siteLinkListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var Deserializer
	 */
	private $fingerprintDeserializer;

	public function __construct(
		Deserializer $idDeserializer,
		Deserializer $siteLinkListDeserializer,
		Deserializer $statementDeserializer,
		Deserializer $fingerprintDeserializer
	) {
		$this->idDeserializer = $idDeserializer;
		$this->siteLinkListDeserializer = $siteLinkListDeserializer;
		$this->statementDeserializer = $statementDeserializer;
		$this->fingerprintDeserializer = $fingerprintDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Item
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Item serialization should be an array' );
		}

		return new Item(
			$this->getItemId( $serialization ),
			$this->fingerprintDeserializer->deserialize( $serialization ),
			$this->getSiteLinkList( $serialization ),
			$this->getStatementList( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @return ItemId|null
	 */
	private function getItemId( array $serialization ) {
		if ( array_key_exists( 'entity', $serialization ) ) {
			return $this->idDeserializer->deserialize( $serialization['entity'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return SiteLinkList|null
	 */
	private function getSiteLinkList( array $serialization ) {
		if ( array_key_exists( 'links', $serialization ) ) {
			return $this->siteLinkListDeserializer->deserialize( $serialization['links'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return StatementList
	 */
	private function getStatementList( array $serialization ) {
		$serialization = $this->normalizeLegacyClaimKeys( $serialization );

		$statementList = new StatementList();

		foreach ( $this->getArrayFromKey( 'claims', $serialization ) as $claimSerialization ) {
			$this->assertClaimValueIsArray( $claimSerialization );
			$statementList->addStatement( $this->getStatement( $claimSerialization ) );
		}

		return $statementList;
	}

	/**
	 * @param array $claimSerialization
	 *
	 * @return Statement
	 */
	private function getStatement( array $claimSerialization ) {
		$statementSerialization = $this->normalizeStatementSerialization( $claimSerialization );

		return $this->statementDeserializer->deserialize( $statementSerialization );
	}

	private function assertClaimValueIsArray( $value ) {
		if ( !is_array( $value ) ) {
			throw new DeserializationException( 'Claim serialization must be an array.' );
		}
	}

	private function normalizeLegacyClaimKeys( array $serialization ) {
		// Compatibility with DataModel 0.2 and 0.3 ItemObjects.
		// (statements key got renamed to claims)
		if ( array_key_exists( 'statements', $serialization ) ) {
			$serialization['claims'] = $serialization['statements'];
			unset( $serialization['statements'] );
		}

		return $serialization;
	}

	private function normalizeStatementSerialization( array $seralization ) {
		if ( !isset( $seralization['rank'] ) ) {
			$seralization['rank'] = Statement::RANK_NORMAL;
		}

		if ( !isset( $seralization['refs'] ) ) {
			$seralization['refs'] = [];
		}

		return $seralization;
	}

	private function getArrayFromKey( $key, array $serialization ) {
		if ( !array_key_exists( $key, $serialization ) ) {
			return [];
		}

		if ( !is_array( $serialization[$key] ) ) {
			throw new InvalidAttributeException(
				$key,
				$serialization[$key],
				'The ' . $key . ' key should point to an array'
			);
		}

		return $serialization[$key];
	}

	/**
	 * @see DispatchableDeserializer::isDeserializerFor
	 *
	 * @since 2.2
	 *
	 * @param mixed $serialization
	 *
	 * @return bool
	 */
	public function isDeserializerFor( $serialization ) {
		return is_array( $serialization )
			// This element is called 'id' in the current serialization.
			&& array_key_exists( 'entity', $serialization )
			&& !array_key_exists( 'datatype', $serialization );
	}

}
