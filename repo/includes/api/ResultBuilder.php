<?php

namespace Wikibase\Api;

use ApiResult;
use InvalidArgumentException;
use Revision;
use Status;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityRevision;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Reference;

/**
 * Builder for Api Results
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	protected $result;

	/**
	 * @var int
	 */
	protected $missingEntityCounter;

	/**
	 * @var SerializerFactory
	 */
	protected $serializerFactory;
	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @param ApiResult $result
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializerFactory $serializerFactory
	 *
	 * @throws InvalidArgumentException
	 * @todo require SerializerFactory
	 */
	public function __construct(
		$result,
		EntityTitleLookup $entityTitleLookup,
		SerializerFactory $serializerFactory = null
	) {
		if( !$result instanceof ApiResult ){
			throw new InvalidArgumentException( 'Result builder must be constructed with an ApiWikibase' );
		}

		if ( $serializerFactory === null ) {
			$serializerFactory = new SerializerFactory();
		}

		$this->result = $result;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->serializerFactory = $serializerFactory;
		$this->missingEntityCounter = -1;
	}

	/**
	 * @return ApiResult
	 */
	private function getResult(){
		return $this->result;
	}

	/**
	 * @since 0.5
	 *
	 * @param $success bool|int|null
	 *
	 * @throws InvalidArgumentException
	 */
	public function markSuccess( $success = true ) {
		$value = intval( $success );
		if( $value !== 1 && $value !== 0 ){
			throw new InvalidArgumentException( '$wasSuccess must evaluate to either 1 or 0 when using intval()' );
		}
		$this->result->addValue( null, 'success', $value );
	}

	/**
	 * @see ApiResult::addValue
	 * @see ApiResult::setIndexedTagName
	 *
	 * @param $path array|string|null
	 * @param $value mixed
	 * @param $name string
	 * @param string $tag Tag name
	 */
	public function addValue( $path, $value, $name, $tag ){
		if ( $this->getResult()->getIsRawMode() ) {
			$this->getResult()->setIndexedTagName( $value, $tag );
		}
		$this->getResult()->addValue( $path, $name, $value );
	}

	/**
	 * Get serialized entity for the EntityRevision and add it to the result
	 *
	 * @param EntityRevision $entityRevision
	 * @param SerializationOptions $options
	 * @param array $props
	 */
	public function addEntityRevision( EntityRevision $entityRevision, $options, array $props = array() ) {
		$entity = $entityRevision->getEntity();
		$entityId = $entity->getId();
		$record = array();

		//if there are no props defined only return type and id..
		if ( $props === array() ) {
			$record['id'] = $entityId->getSerialization();
			$record['type'] = $entityId->getEntityType();
		} else {
			if ( in_array( 'info', $props ) ) {
				$title = $this->entityTitleLookup->getTitleForId( $entityId );
				$record['pageid'] = $title->getArticleID();
				$record['ns'] = intval( $title->getNamespace() );
				$record['title'] = $title->getPrefixedText();
				$record['lastrevid'] = intval( $entityRevision->getRevision() );
				$record['modified'] = wfTimestamp( TS_ISO_8601, $entityRevision->getTimestamp() );
			}

			$serializerFactory = new SerializerFactory();
			$entitySerializer = $serializerFactory->newSerializerForObject( $entity, $options );
			$entitySerialization = $entitySerializer->getSerialized( $entity );

			$record = array_merge( $record, $entitySerialization );
		}

		// key should be numeric to get the correct behavior
		// note that this setting depends upon "setIndexedTagName_internal"
		// NOTE see https://bugzilla.wikimedia.org/show_bug.cgi?id=57529
		$resultName = !$this->getResult()->getIsRawMode() ? $entityId->getSerialization() : null;

		$this->getResult()->addValue( array( 'entities' ), $resultName, $record );
	}

	/**
	 * Get serialized information for the EntityId and add them to result
	 *
	 * @param EntityId $entityId
	 * @param string|array|null $path
	 * @param bool $forceNumericId should we force use the numeric id instead of serialization?
	 * @todo once linktitles breaking change made remove $forceNumericId
	 */
	public function addBasicEntityInformation( EntityId $entityId, $path, $forceNumericId = false ){
		if( $forceNumericId ) {
			//FIXME: this is a very nasty hack as we presume IDs are always prefixed by a single letter
			$this->getResult()->addValue( $path, 'id', substr( $entityId->getSerialization(), 1 ) );
		} else {
			$this->getResult()->addValue( $path, 'id', $entityId->getSerialization() );
		}
		$this->getResult()->addValue( $path, 'type', $entityId->getEntityType() );
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addLabels( array $labels, $path ) {
		$options = new SerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$labelSerializer = $this->serializerFactory->newLabelSerializer( $options );

		$value = $labelSerializer->getSerialized( $labels );
		$this->addValue( $path, $value, 'labels', 'label' );
	}

	/**
	 * Get serialized descriptions and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 */
	public function addDescriptions( array $descriptions, $path ) {
		$options = new SerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$descriptionSerializer = $this->serializerFactory->newDescriptionSerializer( $options );

		$value = $descriptionSerializer->getSerialized( $descriptions );
		$this->addValue( $path, $value, 'descriptions', 'description' );
	}

	/**
	 * Get serialized aliases and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addAliases( array $aliases, $path ) {
		$options = new SerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$aliasSerializer = $this->serializerFactory->newAliasSerializer( $options );
		$value = $aliasSerializer->getSerialized( $aliases );
		$this->addValue( $path, $value, 'aliases', 'alias' );
	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $siteLinks the site links to insert in the result, as SiteLink objects
	 * @param array|string $path where the data is located
	 * @param array|null $options
	 */
	public function addSiteLinks( array $siteLinks, $path, $options = null ) {
		$serializerOptions = new SerializationOptions();
		$serializerOptions->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_NONE );
		$serializerOptions->setIndexTags( $this->getResult()->getIsRawMode() );

		if ( is_array( $options ) ) {
			if ( in_array( EntitySerializer::SORT_ASC, $options ) ) {
				$serializerOptions->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_ASC );
			} elseif ( in_array( EntitySerializer::SORT_DESC, $options ) ) {
				$serializerOptions->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_DESC );
			}

			if ( in_array( 'url', $options ) ) {
				$serializerOptions->addToOption( EntitySerializer::OPT_PARTS, "sitelinks/urls" );
			}

			if ( in_array( 'removed', $options ) ) {
				$serializerOptions->addToOption( EntitySerializer::OPT_PARTS, "sitelinks/removed" );
			}
		}

		$siteLinkSerializer = $this->serializerFactory->newSiteLinkSerializer( $serializerOptions );
		$value = $siteLinkSerializer->getSerialized( $siteLinks );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, 'sitelink' );
			}

			$this->getResult()->addValue( $path, 'sitelinks', $value );
		}
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @since 0.5
	 *
	 * @param Claim[] $claims the labels to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addClaims( array $claims, $path ) {
		$options = new SerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$claimsSerializer = $this->serializerFactory->newClaimsSerializer( $options );

		$value = $claimsSerializer->getSerialized( new Claims( $claims ) );
		$this->addValue( $path, $value, 'claims', 'claim' );
	}

	/**
	 * Get serialized claim and add it to result
	 * @param Claim $claim
	 */
	public function addClaim( Claim $claim ) {
		$options = new SerializationOptions();
		$serializer = $this->serializerFactory->newClaimSerializer( $options );
		$value = $serializer->getSerialized( $claim );
		$this->addValue( null, $value, 'claim', 'claim' );
	}

	/**
	 * Get serialized reference and add it to result
	 * @param Reference $reference
	 */
	public function addReference( Reference $reference ) {
		$options = new SerializationOptions();
		$serializer = $this->serializerFactory->newReferenceSerializer( $options );
		$value = $serializer->getSerialized( $reference );
		$this->addValue( null, $value, 'reference', 'reference' );
	}

	/**
	 * Add an entry for a missing entity...
	 * @param array $missingDetails array containing key value pair missing details
	 */
	public function addMissingEntity( $missingDetails ){
		//@todo fix Bug 45509 (useless missing attribute in xml...)
		$this->getResult()->addValue(
			'entities',
			(string)$this->missingEntityCounter,
			array_merge( $missingDetails, array( 'missing' => "" ) )
		);
		$this->missingEntityCounter--;
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $name
	 */
	public function addNormalizedTitle( $from, $to, $name = 'n' ){
		$this->getResult()->addValue(
			'normalized',
			$name,
			array( 'from' => $from, 'to' => $to )
		);
	}

	/**
	 * Adds the ID of the new revision from the Status object to the API result structure.
	 * The status value is expected to be structured in the way that EditEntity::attemptSave()
	 * resp WikiPage::doEditContent() do it: as an array, with the new revision object in the
	 * 'revision' field.
	 *
	 * If no revision is found the the Status object, this method does nothing.
	 *
	 * @see ApiResult::addValue()
	 *
	 * @param Status $status The status to get the revision ID from.
	 * @param string|null|array $path Where in the result to put the revision id
	 */
	public function addRevisionIdFromStatusToResult( Status $status, $path ) {
		$statusValue = $status->getValue();

		/* @var Revision $revision */
		$revision = isset( $statusValue['revision'] )
			? $statusValue['revision'] : null;

		if ( $revision ) {
			$this->getResult()->addValue(
				$path,
				'lastrevid',
				intval( $revision->getId() )
			);
		}

	}

}
