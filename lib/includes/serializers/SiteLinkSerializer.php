<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use SiteStore;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Serializer for sitelinks.
 *
 * See docs/json.wiki for details of the format.
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
	 * @var SiteStore $siteStore
	 */
	private $siteStore;

	/**
	 * @since 0.4
	 *
	 * @param SerializationOptions $options
	 * @param SiteStore $siteStore
	 */
	public function __construct( SerializationOptions $options, SiteStore $siteStore ) {
		$options->initOption( EntitySerializer::OPT_PARTS,  array(
			'sitelinks',
		) );

		parent::__construct( $options );

		$this->siteStore = $siteStore;
	}

	/**
	 * Returns a serialized array of sitelinks.
	 *
	 * @since 0.4
	 *
	 * @param SiteLink[] $siteLinks
	 *
	 * @return array[]
	 * @throws InvalidArgumentException
	 */
	final public function getSerialized( $siteLinks ) {
		if ( !is_array( $siteLinks ) ) {
			throw new InvalidArgumentException( 'SiteLinkSerializer can only serialize an array of sitelinks' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$parts = $this->options->getOption( EntitySerializer::OPT_PARTS );

		$includeUrls = in_array( 'sitelinks/urls', $parts );
		$setRemoved = in_array( 'sitelinks/removed' , $parts );

		foreach ( $this->sortSiteLinks( $siteLinks ) as $siteLink ) {
			$siteId = $siteLink->getSiteId();
			$pageName = $siteLink->getPageName();

			$response = array(
				'site' => $siteId,
				'title' => $pageName
			);

			if ( $includeUrls ) {
				$site = $this->siteStore->getSite( $siteId );

				if ( $site !== null ) {
					$response['url'] = $site->getPageUrl( $pageName );
				}
			}

			if ( !$setRemoved ) {
				$badges = array();

				foreach ( $siteLink->getBadges() as $badge ) {
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
				$serialization[$siteLink->getSiteId()] = $response;
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
	 * @return SiteLink[]
	 */
	protected function sortSiteLinks( $siteLinks ) {
		$unsortedSiteLinks = $siteLinks;
		$sortDirection = $this->options->getOption( EntitySerializer::OPT_SORT_ORDER );

		if ( $sortDirection !== EntitySerializer::SORT_NONE ) {
			$sortOk = false;

			if ( $sortDirection === EntitySerializer::SORT_ASC ) {
				$sortOk = usort(
					$siteLinks,
					function( SiteLink $a, SiteLink $b ) {
						return strcmp( $a->getSiteId(), $b->getSiteId() );
					}
				);
			} elseif ( $sortDirection === EntitySerializer::SORT_DESC ) {
				$sortOk = usort(
					$siteLinks,
					function( SiteLink $a, SiteLink $b ) {
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
	 * @return SiteLink[]
	 * @throws InvalidArgumentException
	 */
	public function newFromSerialization( array $data ) {
		$siteLinks = array();

		foreach( $data as $siteLink ) {
			if ( !array_key_exists( 'site', $siteLink ) || !array_key_exists( 'title', $siteLink ) ) {
				throw new InvalidArgumentException( 'Site link serialization is invalid.' );
			}

			$badges = array();
			if ( array_key_exists( 'badges', $siteLink ) ) {
				$badges = $this->extractBadges( $siteLink['badges'] );
			}

			$siteLinks[] = new SiteLink( $siteLink['site'], $siteLink['title'], $badges );
		}

		return $siteLinks;
	}

	/**
	 * @param array $data
	 *
	 * @throws InvalidArgumentException
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
