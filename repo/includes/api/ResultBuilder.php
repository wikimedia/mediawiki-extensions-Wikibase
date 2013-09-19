<?php

namespace Wikibase\Api;

use ApiBase, ApiResult, Status;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Lib\Serializers\AliasSerializer;
use Wikibase\Lib\Serializers\ClaimsSerializer;
use Wikibase\Lib\Serializers\DescriptionSerializer;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\LabelSerializer;
use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Serializers\SiteLinkSerializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * //todo builder!?????
 */
class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	protected $result;

	function __construct( ApiResult $result ) {
		if( !$result instanceof ApiResult ){
			throw new \InvalidArgumentException( 'Result builder must be constructed with an ApiWikibase' );
		}

		$this->result = $result;
		$this->indexTags = $result->getIsRawMode();
		$this->serializerFactory = new SerializerFactory();
	}

	protected function getSerializer( $object ){
		$serializer = $this->serializerFactory->newSerializerForObject( $object );
		$serializer->getOptions()->setIndexTags( $this->result->getIsRawMode() );
		return $serializer;
	}

	/**
	 * Adds the standard success flag to the result
	 *
	 * @param bool $success
	 * @param null $path
	 * @param string $key
	 */
	public function markSuccess( $success = true, $path = null , $key = 'success' ){
		$this->result->addValue( $path, $key, intval( $success ) );
	}

	public function addRevisionId( $revid , $path = 'pageinfo', $key = 'lastrevid' ) {
		$this->result->addValue( $path, $key, intval( $revid ) );
	}

	//todo test
	public function addArray( $array, $path = null, $key = null ){
		$this->result->setIndexedTagName( $array, $key );
		$this->result->addValue( null, $path, $array );
	}

	/**
	 * Get serialized claim and add it to result
	 *
	 * @param Claim $claim the claim to set in result
	 * @param array|string|null $path where the data is located
	 * @param string $key name used for the entry
	 */
	public function addClaim( Claim $claim, $path = null , $key = 'claim' ) {
		$this->result->addValue( $path, $key, $this->getSerializer( $claim )->getSerialized( $claim ) );
	}

	/**
	 * Get serialized aliases and add them to result
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string|null $path where the data is located
	 * @param string $key name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 */
	public function addAliasesToResult( array $aliases, $path = null, $key = 'aliases', $tag = 'alias' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->indexTags );
		$aliasSerializer = new AliasSerializer( $options );
		$value = $aliasSerializer->getSerialized( $aliases );

		if ( $value !== array() ) {
			if ( $this->indexTags ) {
				$this->result->setIndexedTagName( $value, $tag );
			}
			$this->result->addValue( $path, $key, $value );
		}

	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @param array $siteLinks the site links to insert in the result, as SiteLink objects
	 * @param array|string|null $path where the data is located
	 * @param string $key name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 * @param array $options additional information to include in the listelinks structure. For example:
	 *              * 'url' will include the full URL of the sitelink in the result
	 *              * 'removed' will mark the sitelinks as removed
	 */
	public function addSiteLinksToResult( array $siteLinks, $path = null, $key = 'sitelinks', $tag = 'sitelink', $options = null ) {
		$serializerOptions = new EntitySerializationOptions( WikibaseRepo::getDefaultInstance()->getIdFormatter() );
		$serializerOptions->setSortDirection( EntitySerializationOptions::SORT_NONE );
		$serializerOptions->setIndexTags( $this->indexTags );

		if ( isset( $options ) ) {
			if ( in_array( EntitySerializationOptions::SORT_ASC, $options ) ) {
				$serializerOptions->setSortDirection( EntitySerializationOptions::SORT_ASC );
			} elseif ( in_array( EntitySerializationOptions::SORT_DESC, $options ) ) {
				$serializerOptions->setSortDirection( EntitySerializationOptions::SORT_DESC );
			}

			if ( in_array( 'url', $options ) ) {
				$serializerOptions->addProp( 'sitelinks/urls' );
			}

			if ( in_array( 'removed', $options ) ) {
				$serializerOptions->addProp( 'sitelinks/removed' );
			}
		}

		$siteStore = \SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $serializerOptions, $siteStore );
		$value = $siteLinkSerializer->getSerialized( $siteLinks );

		if ( $value !== array() ) {
			if ( $this->indexTags ) {
				$this->result->setIndexedTagName( $value, $tag );
			}

			$this->result->addValue( $path, $key, $value );
		}
	}

	/**
	 * Get serialized descriptions and add them to result
	 *
	 * @param array $descriptions the descriptions to insert in the result
	 * @param array|string|null $path where the data is located
	 * @param string $key name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 */
	public function addDescriptionsToResult( array $descriptions, $path = null, $key = 'descriptions', $tag = 'description' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->indexTags );
		$descriptionSerializer = new DescriptionSerializer( $options );

		$value = $descriptionSerializer->getSerialized( $descriptions );

		if ( $value !== array() ) {
			if ( $this->indexTags ) {
				$this->result->setIndexedTagName( $value, $tag );
			}

			$this->result->addValue( $path, $key, $value );
		}
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string|null $path where the data is located
	 * @param string $key name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 */
	public function addLabelsToResult( array $labels, $path = null, $key = 'labels', $tag = 'label' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->indexTags );
		$labelSerializer = new LabelSerializer( $options );

		$value = $labelSerializer->getSerialized( $labels );

		if ( $value !== array() ) {
			if ( $this->indexTags ) {
				$this->result->setIndexedTagName( $value, $tag );
			}

			$this->result->addValue( $path, $key, $value );
		}
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @param array $claims the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 * @todo test
	 */
	public function addClaimsToResult( array $claims, $path, $name = 'claims', $tag = 'claim' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->indexTags );
		$claimSerializer = new ClaimsSerializer( $options );

		$value = $claimSerializer->getSerialized( new Claims( $claims ) );

		if ( $value !== array() ) {
			if ( $this->indexTags ) {
				$this->result->setIndexedTagName( $value, $tag );
			}

			$this->result->addValue( $path, $name, $value );
		}
	}

	//todo test
	public function addRevisionIdFromStatus( Status $status, $path = 'pageinfo', $key = 'lastrevid' ) {
		$statusValue = $status->getValue();

		/* @var \Revision $revision */
		$revision = isset( $statusValue['revision'] )
			? $statusValue['revision'] : null;

		if ( $revision ) {
			$this->addRevisionId( $revision->getId(), $path, $key );
		}

	}

}