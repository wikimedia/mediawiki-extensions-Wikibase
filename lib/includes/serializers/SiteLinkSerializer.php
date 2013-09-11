<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\SiteLink;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Serializer for sitelinks.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkSerializer extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.4
	 *
	 * @var EntitySerializationOptions
	 */
	protected $options;

	/**
	 * @since 0.4
	 *
	 * @var SiteSQLStore $siteStore
	 */
	protected $siteStore;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param EntitySerializationOptions $options
	 * @param SiteSQLStore $siteStore
	 */
	public function __construct( EntitySerializationOptions $options, \SiteSQLStore $siteStore ) {
		$this->options = new MultiLangSerializationOptions();
		$this->siteStore = $siteStore;
		parent::__construct( $options );
	}

	/**
	 * Returns a serialized array of sitelinks.
	 *
	 * @since 0.4
	 *
	 * @param array $sitelinks
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final public function getSerialized( $siteLinks ) {
		if ( !is_array( $siteLinks ) ) {
			throw new InvalidArgumentException( 'SiteLinkSerializer can only serialize an array of sitelinks' );
		}

		$serialization = array();

		$includeUrls = in_array( 'sitelinks/urls', $this->options->getProps() );
		$setRemoved = in_array( 'sitelinks/removed', $this->options->getProps() );

		foreach ( $this->sortSiteLinks( $siteLinks ) as $link ) {
			$response = array(
				'site' => $link->getSiteId(),
				'title' => $link->getPageName(),
			);

			if ( $includeUrls ) {
				$site = $this->siteStore->getSite( $link->getSiteId() );

				if ( $site !== null ) {
					$siteLink = new SiteLink( $site, $link->getPageName() );
					$response['url'] = $siteLink->getUrl();
				}
			}

			if ( !$setRemoved ) {
				$badges = array();

				foreach ( $link->getBadges() as $badge ) {
					$badges[] = $badge->getSerialization();
				}

				if ( $this->options->shouldIndexTags() ) {
					$this->setIndexedTagName( $badges, 'badge' );
				}

				$response['badges'] = $badges;
			}

			if ( $setRemoved ) {
				$response['removed'] = '';
			}

			if ( !$this->options->shouldIndexTags() ) {
				$serialization[$link->getSiteId()] = $response;
			}
			else {
				$serialization[] = $response;
			}
		}

		if ( $this->options->shouldIndexTags() ) {
			$this->setIndexedTagName( $serialization, 'sitelink' );
		}

		return $serialization;
	}

	/**
	 * Sorts the siteLinks according to the options.
	 *
	 * @since 0.4
	 *
	 * @param array $siteLinks
	 * @return SimpleSiteLink[]
	 */
	protected function sortSiteLinks( $siteLinks ) {
		$unsortedSiteLinks = $siteLinks;
		$sortDirection = $this->options->getSortDirection();

		if ( $sortDirection !== EntitySerializationOptions::SORT_NONE ) {
			$sortOk = false;

			if ( $sortDirection === EntitySerializationOptions::SORT_ASC ) {
				$sortOk = usort(
					$siteLinks,
					function( SimpleSiteLink $a, SimpleSiteLink $b ) {
						return strcmp( $a->getSiteId(), $b->getSiteId() );
					}
				);
			} elseif ( $sortDirection === EntitySerializationOptions::SORT_DESC ) {
				$sortOk = usort(
					$siteLinks,
					function( SimpleSiteLink $a, SimpleSiteLink $b ) {
						return strcmp( $b->getSiteId(), $a->getSiteId() );
					}
				);
			}

			if ( !$sortOk ) {
				$siteLinks = $unsortedSiteLinks;
			}
		}

		return $siteLinks;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.5
	 *
	 * @param array $data
	 *
	 * @return SimpleSiteLink[]
	 * @throws InvalidArgumentException
	 */
	public function newFromSerialization( array $data ) {
		$siteLinks = array();

		foreach( $data as $siteLink ) {
			if ( !array_key_exists( 'site', $siteLink ) || !array_key_exists( 'title', $siteLink ) ) {
				throw new InvalidArgumentException( 'Site link serialization is invalid.' );
			}

			if ( array_key_exists( 'badges', $siteLink ) ) {
				$badges = $this->extractBadges( $siteLink['badges'] );
			}

			$siteLinks[] = new SimpleSiteLink( $siteLink['site'], $siteLink['title'], $badges );
		}

		return $siteLinks;
	}

	/**
	 * @param array $badges
	 *
	 * @return ItemId[]
	 */
	protected function extractBadges( array $data ) {
		$idParser = new BasicEntityIdParser();

		$badges = array();

		foreach( $data as $badge ) {
			$itemId = $idParser->parse( $badge );

			if ( ! $itemId instanceof ItemId ) {
				throw new InvalidArgumentException( 'Site link badges must be valid item ids.' );
			}

			$badges[] = $itemId;

		}

		return $badges;
	}
}
