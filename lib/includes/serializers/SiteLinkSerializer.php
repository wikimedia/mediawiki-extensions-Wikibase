<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\SiteLink;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Serializer for sitelinks.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.4
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Michał Łazowik
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
	public final function getSerialized( $siteLinks ) {
		if ( !is_array( $siteLinks ) ) {
			throw new InvalidArgumentException( 'SiteLinkSerializer can only serialize an array of sitelinks' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$includeUrls = in_array( 'sitelinks/urls', $this->options->getProps() );
		$includeBadges = in_array( 'sitelinks/badges' , $this->options->getProps() );

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

			if ( $includeBadges ) {
				$badges = array();

				foreach ( $link->getBadges() as $badge ) {
					$badges[] = $badge->getSerialization();
				}

				$this->setIndexedTagName( $badges , 'badge' );

				$response['badges'] = $badges;
			}

			if ( in_array( 'sitelinks/removed', $this->options->getProps() ) ) {
				$response['removed'] = '';
			}

			if ( $this->options->shouldUseKeys() ) {
				$serialization[$link->getSiteId()] = $response;
			}
			else {
				$serialization[] = $response;
			}
		}

		if ( !$this->options->shouldUseKeys() ) {
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
}
