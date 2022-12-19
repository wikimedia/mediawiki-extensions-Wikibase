<?php

namespace Wikibase\DataModel\Serializers;

use InvalidArgumentException;
use Serializers\DispatchableSerializer;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;

/**
 * Factory for constructing Serializer objects that can serialize WikibaseDataModel objects.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class SerializerFactory {

	public const OPTION_DEFAULT = 0;
	/** @since 1.2.0 */
	public const OPTION_OBJECTS_FOR_MAPS = 1;
	/**
	 * @since 1.7.0
	 * @deprecated since 2.5 use OPTION_SERIALIZE_SNAKS_WITHOUT_HASH
	 */
	public const OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH = 2;
	/**
	 * @since 1.7.0
	 * @deprecated since 2.5 use OPTION_SERIALIZE_SNAKS_WITHOUT_HASH
	 */
	public const OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH = 4;
	/**
	 * @since 1.7.0
	 * @deprecated since 2.5 use OPTION_SERIALIZE_SNAKS_WITHOUT_HASH
	 */
	public const OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH = 8;
	/**
	 * Omit hashes when serializing snaks.
	 * @since 2.5.0
	 */
	public const OPTION_SERIALIZE_SNAKS_WITHOUT_HASH = 14; /* =
		self::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH |
		self::OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH |
		self::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH; */

	/**
	 * @var int
	 */
	private $options;

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer serializer for DataValue objects
	 * @param int $options set multiple with bitwise or
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Serializer $dataValueSerializer, $options = 0 ) {
		if ( !is_int( $options ) ) {
			throw new InvalidArgumentException( '$options must be an integer' );
		}

		$this->dataValueSerializer = $dataValueSerializer;
		$this->options = $options;
	}

	/**
	 * @return bool
	 */
	private function shouldUseObjectsForMaps() {
		return (bool)( $this->options & self::OPTION_OBJECTS_FOR_MAPS );
	}

	/**
	 * @return bool
	 */
	private function shouldSerializeMainSnaksWithHash() {
		return !(bool)( $this->options & self::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH );
	}

	/**
	 * @return bool
	 */
	private function shouldSerializeQualifierSnaksWithHash() {
		return !(bool)( $this->options & self::OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH );
	}

	/**
	 * @return bool
	 */
	private function shouldSerializeReferenceSnaksWithHash() {
		return !(bool)( $this->options & self::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH );
	}

	/**
	 * @return DispatchableSerializer A serializer that can only serialize Item and Property
	 *  objects, but no other entity types. In contexts with custom entity types other than items
	 *  and properties this is not what you want. If in doubt, favor a custom
	 *  `DispatchingSerializer` containing the exact entity serializers you need.
	 */
	public function newEntitySerializer() {
		return new DispatchingSerializer( [
			$this->newItemSerializer(),
			$this->newPropertySerializer(),
		] );
	}

	/**
	 * Returns a Serializer that can serialize Item objects.
	 *
	 * @since 2.1
	 */
	public function newItemSerializer(): ItemSerializer {
		return new ItemSerializer(
			$this->newTermListSerializer(),
			$this->newAliasGroupListSerializer(),
			$this->newStatementListSerializer(),
			$this->newSiteLinkSerializer(),
			$this->shouldUseObjectsForMaps()
		);
	}

	/**
	 * Returns a Serializer that can serialize Property objects.
	 *
	 * @since 2.1
	 */
	public function newPropertySerializer(): PropertySerializer {
		return new PropertySerializer(
			$this->newTermListSerializer(),
			$this->newAliasGroupListSerializer(),
			$this->newStatementListSerializer()
		);
	}

	/**
	 * Returns a Serializer that can serialize SiteLink objects.
	 */
	public function newSiteLinkSerializer(): SiteLinkSerializer {
		return new SiteLinkSerializer();
	}

	/**
	 * Returns a Serializer that can serialize StatementList objects.
	 *
	 * @since 1.4
	 */
	public function newStatementListSerializer(): StatementListSerializer {
		return new StatementListSerializer(
			$this->newStatementSerializer(),
			$this->shouldUseObjectsForMaps()
		);
	}

	/**
	 * Returns a Serializer that can serialize Statement objects.
	 *
	 * @since 1.4
	 */
	public function newStatementSerializer(): StatementSerializer {
		return new StatementSerializer(
			$this->newSnakSerializer( $this->shouldSerializeMainSnaksWithHash() ),
			$this->newSnakListSerializer( $this->shouldSerializeQualifierSnaksWithHash() ),
			$this->newReferencesSerializer()
		);
	}

	/**
	 * Returns a Serializer that can serialize ReferenceList objects.
	 */
	public function newReferencesSerializer(): ReferenceListSerializer {
		return new ReferenceListSerializer( $this->newReferenceSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Reference objects.
	 */
	public function newReferenceSerializer(): ReferenceSerializer {
		return new ReferenceSerializer(
			$this->newSnakListSerializer(
				$this->shouldSerializeReferenceSnaksWithHash()
			)
		);
	}

	/**
	 * Returns a Serializer that can serialize SnakList objects.
	 *
	 * @param bool $serializeSnaksWithHash
	 *
	 * @since 1.4
	 */
	public function newSnakListSerializer( $serializeSnaksWithHash = true ): SnakListSerializer {
		return new SnakListSerializer(
			$this->newSnakSerializer( $serializeSnaksWithHash ),
			$this->shouldUseObjectsForMaps()
		);
	}

	/**
	 * Returns a Serializer that can serialize Snak objects.
	 *
	 * @param bool $serializeWithHash
	 */
	public function newSnakSerializer( $serializeWithHash = true ): SnakSerializer {
		return new SnakSerializer( $this->dataValueSerializer, $serializeWithHash );
	}

	/**
	 * Returns a Serializer that can serialize TypedSnak objects.
	 *
	 * @param bool $serializeWithHash
	 *
	 * @since 1.3
	 */
	public function newTypedSnakSerializer( $serializeWithHash = true ): TypedSnakSerializer {
		return new TypedSnakSerializer( $this->newSnakSerializer( $serializeWithHash ) );
	}

	/**
	 * Returns a Serializer that can serialize Term objects.
	 *
	 * @since 1.5
	 */
	public function newTermSerializer(): TermSerializer {
		return new TermSerializer();
	}

	/**
	 * Returns a Serializer that can serialize TermList objects.
	 *
	 * @since 1.5
	 */
	public function newTermListSerializer(): TermListSerializer {
		return new TermListSerializer( $this->newTermSerializer(), $this->shouldUseObjectsForMaps() );
	}

	/**
	 * Returns a Serializer that can serialize AliasGroup objects.
	 *
	 * @since 1.6
	 */
	public function newAliasGroupSerializer(): AliasGroupSerializer {
		return new AliasGroupSerializer();
	}

	/**
	 * Returns a Serializer that can serialize AliasGroupList objects.
	 *
	 * @since 1.5
	 */
	public function newAliasGroupListSerializer(): AliasGroupListSerializer {
		return new AliasGroupListSerializer(
			$this->newAliasGroupSerializer(),
			$this->shouldUseObjectsForMaps()
		);
	}

}
