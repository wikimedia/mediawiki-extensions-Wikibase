<?php

namespace Wikibase\Api;

use ApiResult;
use InvalidArgumentException;
use Wikibase\Claim;
use Wikibase\Claims;
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
	 * @param ApiResult $result
	 * @param SerializerFactory $serializerFactory
	 *
	 * @throws \InvalidArgumentException
	 * @todo require SerializerFactory
	 */
	public function __construct( $result, SerializerFactory $serializerFactory = null ) {
		if( !$result instanceof ApiResult ){
			throw new InvalidArgumentException( 'Result builder must be constructed with an ApiWikibase' );
		}

		if ( $serializerFactory === null ) {
			$serializerFactory = new SerializerFactory();
		}

		$this->serializerFactory = $serializerFactory;
		$this->result = $result;
		$this->missingEntityCounter = -1;
	}

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
	public function markSuccess( $success ) {
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
	 */
	public function addNormalizedTitle( $from, $to ){
		$this->getResult()->addValue(
			'normalized',
			'n',
			array( 'from' => $from, 'to' => $to )
		);
	}

}
