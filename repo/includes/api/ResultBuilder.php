<?php

namespace Wikibase\Api;

use ApiResult;
use InvalidArgumentException;
use SiteSQLStore;
use Wikibase\Claims;
use Wikibase\Lib\Serializers\AliasSerializer;
use Wikibase\Lib\Serializers\ClaimsSerializer;
use Wikibase\Lib\Serializers\DescriptionSerializer;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\LabelSerializer;
use Wikibase\Lib\Serializers\MultiLangSerializationOptions;
use Wikibase\Lib\Serializers\SiteLinkSerializer;

class ResultBuilder {

	/**
	 * @var ApiResult
	 */
	protected $result;

	/**
	 * @var int
	 */
	protected $missingEntityCounter;

	public function __construct( $result ) {
		if( !$result instanceof ApiResult ){
			throw new InvalidArgumentException( 'Result builder must be constructed with an ApiWikibase' );
		}

		$this->result = $result;
		$this->missingEntityCounter = -1;
	}

	private function getResult(){
		return $this->result;
	}

	/**
	 * @since 0.5
	 *
	 * @param $wasSuccess bool|int|null
	 *
	 * @throws InvalidArgumentException
	 */
	public function markSuccess( $wasSuccess ) {
		$value = intval( $wasSuccess );
		if( $value !== 1 && $value !== 0 ){
			throw new InvalidArgumentException( '$wasSuccess must evaluate to either 1 or 0 when using intval()' );
		}
		$this->result->addValue( null, 'success', $value );
	}

	/**
	 * Get serialized labels and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $labels the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 */
	public function addLabels( array $labels, $path, $name = 'labels', $tag = 'label' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$labelSerializer = new LabelSerializer( $options );

		$value = $labelSerializer->getSerialized( $labels );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Get serialized descriptions and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $descriptions the descriptions to insert in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 */
	public function addDescriptions( array $descriptions, $path, $name = 'descriptions', $tag = 'description' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$descriptionSerializer = new DescriptionSerializer( $options );

		$value = $descriptionSerializer->getSerialized( $descriptions );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Get serialized aliases and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $aliases the aliases to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 */
	public function addAliases( array $aliases, $path, $name = 'aliases', $tag = 'alias' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$aliasSerializer = new AliasSerializer( $options );
		$value = $aliasSerializer->getSerialized( $aliases );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}
			$this->getResult()->addValue( $path, $name, $value );
		}

	}

	/**
	 * Get serialized sitelinks and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $siteLinks the site links to insert in the result, as SiteLink objects
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 * @param array $options additional information to include in the listelinks structure. For example:
	 *              * 'url' will include the full URL of the sitelink in the result
	 *              * 'removed' will mark the sitelinks as removed
	 *
	 */
	public function addSiteLinks( array $siteLinks, $path, $name = 'sitelinks', $tag = 'sitelink', $options = null ) {
		$serializerOptions = new EntitySerializationOptions();
		$serializerOptions->setSortDirection( EntitySerializationOptions::SORT_NONE );
		$serializerOptions->setIndexTags( $this->getResult()->getIsRawMode() );

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

		$siteStore = SiteSQLStore::newInstance();
		$siteLinkSerializer = new SiteLinkSerializer( $serializerOptions, $siteStore );
		$value = $siteLinkSerializer->getSerialized( $siteLinks );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

	/**
	 * Get serialized claims and add them to result
	 *
	 * @since 0.5
	 *
	 * @param array $claims the labels to set in the result
	 * @param array|string $path where the data is located
	 * @param string $name name used for the entry
	 * @param string $tag tag used for indexed entries in xml formats and similar
	 *
	 */
	public function addClaims( array $claims, $path, $name = 'claims', $tag = 'claim' ) {
		$options = new MultiLangSerializationOptions();
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		$claimSerializer = new ClaimsSerializer( $options );

		$value = $claimSerializer->getSerialized( new Claims( $claims ) );

		if ( $value !== array() ) {
			if ( $this->getResult()->getIsRawMode() ) {
				$this->getResult()->setIndexedTagName( $value, $tag );
			}

			$this->getResult()->addValue( $path, $name, $value );
		}
	}

}