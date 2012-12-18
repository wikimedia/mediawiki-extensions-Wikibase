<?php

namespace Wikibase;

class ItemDiffer extends EntityDiffer {

	protected function entityToArray( Entity $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new MWException( 'ItemDiffer only accepts Item objects' );
		}

		$array = parent::entityToArray( $entity );

		$array['links'] = SiteLink::siteLinksToArray( $entity->getSiteLinks() );

		return $array;
	}

}