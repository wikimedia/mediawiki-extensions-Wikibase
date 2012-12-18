<?php

namespace Wikibase;
use MWException;

class ItemPatcher extends EntityPatcher {

	/**
	 * @see EntityPatcher::patchSpecificFields
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param EntityDiff $patch
	 *
	 * @throws MWException
	 */
	protected function patchSpecificFields( Entity &$entity, EntityDiff $patch ) {
		if ( !( $entity instanceof Item ) || !( $patch instanceof ItemDiff ) ) {
			throw new MWException( 'ItemPatcher only deals with Item objects' );
		}

		/**
		 * @var Item $entity
		 * @var ItemDIff $patch
		 */
		$links = SiteLink::siteLinksToArray( $entity->getSiteLinks() );
		$links = $this->mapPatcher->patch( $links, $patch->getSiteLinkDiff() );
		$entity->setSiteLinks( SiteLink::siteLinksFromArray( $links ) );
	}

}