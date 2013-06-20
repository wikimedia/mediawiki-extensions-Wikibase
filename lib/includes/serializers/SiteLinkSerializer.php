<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Serializer for sitelinks.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SiteLinkSerializer extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.4
	 *
	 * @var MultiLangSerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param MultiLangSerializationOptions $options
	 */
	public function __construct( MultiLangSerializationOptions $options = null ) {
		if ( $options === null ) {
			$this->options = new MultiLangSerializationOptions();
		}
		parent::__construct( $options );
	}

	/**
	 * Returns a serialized array of labels.
	 *
	 * @since 0.4
	 *
	 * @param array $sitelinks
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public final function getSerialized( $sitelinks ) {
		if ( !is_array( $sitelinks ) ) {
			throw new InvalidArgumentException( 'SiteLinkSerializer can only serialize an array of sitelinks' );
		}

		$serialization = array();

		$includeUrls = in_array( 'sitelinks/urls', $this->options->getProps() );

		foreach ( $this->getSortedSiteLinks( $item ) as $link ) {
			$response = array(
				'site' => $link->getSiteId(),
				'title' => $link->getPageName(),
			);

			if ( $includeUrls ) {
				// FIXME: deprecated method usage
				$site = \Sites::singleton()->getSite( $link->getSiteId() );

				if ( $site !== null ) {
					$siteLink = new SiteLink( $site, $link->getPageName() );
					$response['url'] = $siteLink->getUrl();
				}
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
	 * Returns the sitelinks for the provided item sorted based
	 * on the set serialization options.
	 *
	 * @since 0.2
	 *
	 * @param Item $item
	 * @return SimpleSiteLink[]
	 */
	protected function getSortedSiteLinks( Item $item ) {
		$siteLinks = $item->getSimpleSiteLinks();

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
				$siteLinks = $item->getSimpleSiteLinks();
			}
		}

		return $siteLinks;
	}
}
