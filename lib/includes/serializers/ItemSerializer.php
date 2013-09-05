<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Entity;
use Wikibase\Item;

/**
 * Serializer for items.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.2
 *
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ItemSerializer extends EntitySerializer {

	/**
	 * @since 0.4
	 *
	 * @var \SiteSQLStore
	 */
	protected $siteStore;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param EntitySerializationOptions $options
	 */
	public function __construct( EntitySerializationOptions $options, \SiteSQLStore $siteStore = null ) {
		if ( $siteStore === null ) {
			$this->siteStore = \SiteSQLStore::newInstance();
		} else {
			$this->siteStore = $siteStore;
		}
		parent::__construct( $options );
	}

	/**
	 * @see EntitySerializer::getEntityTypeSpecificSerialization
	 *
	 * @since 0.2
	 *
	 * @param Entity $item
	 *
	 * @return array
	 * @throws MWException
	 */
	protected function getEntityTypeSpecificSerialization( Entity $item ) {
		if ( !( $item instanceof Item ) ) {
			throw new MWException( 'ItemSerializer can only serialize Item implementing objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		if ( in_array( 'sitelinks', $this->options->getProps() ) ) {
			$siteLinkSerializer = new SiteLinkSerializer( $this->options, $this->siteStore );
			$siteLinks = $item->getSimpleSiteLinks();
			$serialization['sitelinks'] = $siteLinkSerializer->getSerialized( $siteLinks );
		}

		return $serialization;
	}
}
