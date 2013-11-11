<?php

namespace Wikibase\Api;

use ApiResult;
use InvalidArgumentException;
use SiteSQLStore;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Lib\Serializers\AliasSerializer;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\ClaimsSerializer;
use Wikibase\Lib\Serializers\DescriptionSerializer;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\LabelSerializer;
use Wikibase\Lib\Serializers\ReferenceSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Serializers\SiteLinkSerializer;
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
	 * @var SerializationOptions
	 */
	protected $serializationOptions;

	/**
	 * @var int
	 */
	protected $missingEntityCounter;

	//@todo remove this comment, the below is to be merged with daniels
	/**
	 * @var SerializerFactory
	 */
	protected $serializerFactory;

	/**
	 * @param ApiResult $result
	 * @param SerializerFactory $serializerFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ApiResult $result, SerializerFactory $serializerFactory = null ) {
		if( !$result instanceof ApiResult ){
			throw new InvalidArgumentException( 'ResultBuilder must be constructed with an ApiResult' );
		}

		$this->result = $result;
		$this->serializerFactory = $serializerFactory;
		$this->missingEntityCounter = -1;
		$this->serializationOptions = new SerializationOptions();

		//@todo cleanup the below which is to deal with daniels patch
		if( ! $this->serializerFactory instanceof SerializerFactory ){
			$this->serializerFactory = new SerializerFactory();
		}

		if ( method_exists( $this->serializerFactory, 'newSerializationOptions' ) ) {
			$this->serializationOptions = $this->serializerFactory->newSerializationOptions();
		}

		$this->serializationOptions->setIndexTags( $this->getResult()->getIsRawMode() );
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
	 * Get serialized labels and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string $path where the data is located
	 */
	public function addLabels( array $labels, $path ) {
		$labelSerializer = new LabelSerializer( $this->serializationOptions );
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
		$descriptionSerializer = new DescriptionSerializer( $this->serializationOptions );
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
		$aliasSerializer = new AliasSerializer( $this->serializationOptions );
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
		$serializerOptions = $this->serializationOptions;
		$serializerOptions->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_NONE );

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

		$siteStore = SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $serializerOptions, $siteStore );
		$value = $siteLinkSerializer->getSerialized( $siteLinks );

		$this->addValue( $path, $value, 'sitelinks', 'sitelink' );
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
		$claimsSerializer = new ClaimsSerializer( $this->serializationOptions );
		$value = $claimsSerializer->getSerialized( new Claims( $claims ) );
		$this->addValue( $path, $value, 'claims', 'claim' );
	}

	/**
	 * Get serialized claim and add it to result
	 * @param Claim $claim#
	 * @todo test
	 */
	public function addClaim( Claim $claim ) {
		$serializer = new ClaimSerializer( $this->serializationOptions );
		$value = $serializer->getSerialized( $claim );
		$this->addValue( null, $value, 'claim', 'claim' );
	}

	/**
	 * Get serialized reference and add it to result
	 * @param Reference $reference
	 * @todo test
	 */
	public function addReference( Reference $reference ) {
		$serializer = new ReferenceSerializer( $this->serializationOptions );
		$value = $serializer->getSerialized( $reference );
		$this->addValue( null, $value, 'reference', 'reference' );
	}

	/**
	 * @todo test
	 */
	public function addMissingEntity( $siteId, $title ){
		//@todo fix Bug 45509 (useless missing attribute in xml...)
		$this->getResult()->addValue(
			'entities',
			(string)($this->missingEntityCounter),
			array( 'site' => $siteId, 'title' => $title, 'missing' => "" )
		);
		$this->missingEntityCounter--;
	}

}
