<?php declare( strict_types=1 );

namespace Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\MissingAttributeException;
use TypeError;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LegacyStatementDeserializer implements DispatchableDeserializer {

	/**
	 * @var Deserializer
	 */
	private $snakDeserializer;

	/**
	 * @var Deserializer
	 */
	private $snakListDeserializer;

	public function __construct( Deserializer $snakDeserializer, Deserializer $snakListDeserializer ) {
		$this->snakDeserializer = $snakDeserializer;
		$this->snakListDeserializer = $snakListDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Statement
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		if ( !is_array( $serialization ) ) {
			throw new DeserializationException( 'Statement serialization must be an array' );
		}

		$this->assertHasKey( $serialization, 'm', 'Mainsnak serialization is missing' );
		$this->assertHasKey( $serialization, 'q', 'Qualifiers serialization is missing' );
		$this->assertHasKey( $serialization, 'g', 'Guid is missing in serialization' );
		$this->assertHasKey( $serialization, 'rank', 'Rank is missing in serialization' );
		$this->assertHasKey( $serialization, 'refs', 'Refs are missing in serialization' );

		return $this->newStatement( $serialization );
	}

	private function assertHasKey( array $serialization, $key, $message ) {
		if ( !array_key_exists( $key, $serialization ) ) {
			throw new MissingAttributeException( $key, $message );
		}
	}

	private function newStatement( array $serialization ) {
		/* @var Snak $snak */
		$snak = $this->snakDeserializer->deserialize( $serialization['m'] );
		/* @var SnakList $snakList */
		$snakList = $this->snakListDeserializer->deserialize( $serialization['q'] );
		$statement = new Statement( $snak, $snakList, $this->getReferences( $serialization['refs'] ) );

		try {
			$statement->setRank( $serialization['rank'] );
			$statement->setGuid( $serialization['g'] );
		} catch ( TypeError $ex ) {
			// DeserializationException only accepts Exception instead of Throwable, like it's parent
			// TODO: uncomment $ex when DeserializationException accepts Throwable
			throw new DeserializationException( $ex->getMessage() /*, $ex*/ );
		}

		return $statement;
	}

	private function getReferences( array $refs ) {
		$references = [];

		foreach ( $refs as $serialization ) {
			$references[] = new Reference( $this->snakListDeserializer->deserialize( $serialization ) );
		}

		return new ReferenceList( $references );
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
			   // This element is called 'mainsnak' in the current serialization.
			   && array_key_exists( 'm', $serialization );
	}

}
