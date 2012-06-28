<?php

namespace Wikibase;
use MWException;

/**
 * A collection of Site objects.
 *
 * TODO: ensure append works
 * TODO: ensure unset works
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 * @ingroup Sites
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteList extends \ArrayObject /* implements ORMIterator */ {

	/**
	 * Holds the group names (keys) pointing to an arrays
	 * consisting of offset value pointing to their sites global identifier.
	 * @since 0.1
	 * @var array
	 */
	protected $groups = array();

	/**
	 * Local site identifiers pointing to their sites offset value.
	 * @since 0.1
	 * @var array
	 */
	protected $byLocalId = array();

	/**
	 * Global site identifiers pointing to their sites offset value.
	 * @since 0.1
	 * @var array
	 */
	protected $byGlobalId = array();

	/**
	 * @see SiteList::getNewOffset()
	 * @since 0.1
	 * @var integer
	 */
	protected $indexOffset = 0;

	/**
	 * Finds a new offset for when appending an element.
	 * TODO: the base class does this, so it would be better to integrate,
	 * but there does not appear to be any way to do this...
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	protected function getNewOffset() {
		while ( true ) {
			if ( !$this->offsetExists( $this->indexOffset ) ) {
				return $this->indexOffset;
			}

			$this->indexOffset++;
		}
	}

	/**
	 * Constructor.
	 * @see ArrayObject::__construct
	 *
	 * @since 0.1
	 *
	 * @param null $input
	 * @param int $flags
	 * @param string $iterator_class
	 */
	public function __construct( $input = null, $flags = 0, $iterator_class = 'ArrayIterator' ) {
		parent::__construct( array(), $flags, $iterator_class );

		if ( !is_null( $input ) ) {
			array_walk( $input, array( $this, 'append' ) );
		}
	}

	/**
	 * @see ArrayObject::append
	 *
	 * @since 0.1
	 *
	 * @param mixed $value
	 */
	public function append( $value ) {
		$this->offsetSet( null, $value );
	}

	/**
	 * @see ArrayObject::offsetSet()
	 *
	 * @since 0.1
	 *
	 * @param mixed $index
	 * @param Site $site
	 *
	 * @throws MWException
	 */
	public function offsetSet( $index, $site ) {
		if ( !$site instanceof Site ) {
			throw new MWException( 'Can only add Site implementing objects to SiteList.' );
		}

		if ( $this->hasGlobalId( $site->getGlobalId() ) ) {
			$this->removeSiteByGlobalId( $site->getGlobalId() );
		}

		$this->setSite( $index, $site );
	}

	/**
	 * Sets the provided site.
	 * Equivalent behaviour to parent::offsetSet, plus additional indexing.
	 *
	 * @since 0.1
	 *
	 * @param $index
	 * @param Site $site
	 */
	protected function setSite( $index, Site $site ) {
		if ( is_null( $index ) ) {
			$index = $this->getNewOffset();
		}

		$this->byGlobalId[$site->getField( 'global_key' )] = $index;
		$this->byLocalId[$site->getField( 'local_key' )] = $index;

		$group = $site->getField( 'group' );

		if ( !array_key_exists( $group, $this->groups ) ) {
			$this->groups[$group] = array();
		}

		$this->groups[$group][$index] = $site->getField( 'global_key' );

		parent::offsetSet( $index, $site );
	}

	/**
	 * @see ArrayObject::offsetUnset()
	 *
	 * @since 0.1
	 *
	 * @param mixed $index
	 */
	public function offsetUnset( $index ) {
		$site = $this->offsetGet( $index );

		if ( $site !== false ) {
			unset( $this->byGlobalId[$site->getField( 'global_key' )] );
			unset( $this->byLocalId[$site->getField( 'local_key' )] );
			unset( $this->groups[$site->getField( 'group' )][$index] );
		}

		parent::offsetUnset( $index );
	}

	/**
	 * Returns all the global site identifiers.
	 * Optionally only those belonging to the specified group.
	 *
	 * @since 0.1
	 *
	 * @param string|null $groupName
	 *
	 * @return array
	 */
	public function getGlobalIdentifiers( $groupName = null ) {
		if ( is_null( $groupName ) ) {
			return array_keys( $this->byGlobalId );
		}
		else {
			return array_key_exists( $groupName, $this->groups ) ? $this->groups[$groupName] : array();
		}
	}

	/**
	 * Returns the local identifiers.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getLocalIdentifiers() {
		return array_keys( $this->byLocalId );
	}

	/**
	 * Returns the names of the groups represented in this
	 * list of sites.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function getGroupNames() {
		$this->cleanDeadGroups();
		return array_keys( $this->groups );
	}

	/**
	 * Removes the groups without any associated sites
	 * from the groups field.
	 *
	 * @since 0.1
	 */
	protected function cleanDeadGroups() {
		$this->groups = array_filter(
			$this->groups,
			function( array $groupPointers ) {
				return $groupPointers !== array();
			}
		);
	}

	/**
	 * Returns a Sites containing only the sites of the specified group.
	 *
	 * @since 0.1
	 *
	 * @param string $groupName
	 *
	 * @return SiteList
	 */
	public function getGroup( $groupName ) {
		if ( array_key_exists( $groupName, $this->groups ) ) {
			$sites = array();

			foreach ( array_keys( $this->groups[$groupName] ) as $offset ) {
				$sites[$offset] = $this->offsetGet( $offset );
			}
		}
		else {
			$sites = array();
		}

		return new static( $sites );
	}

	/**
	 * Returns if the list contains the site with the provided local site identifier.
	 *
	 * @param string $localSiteId
	 *
	 * @return boolean
	 */
	public function hasLocalId( $localSiteId ) {
		return array_key_exists( $localSiteId, $this->byLocalId );
	}

	/**
	 * Returns the Site with the provided local site id.
	 * The site needs to exist, so if not sure, call hasLocalId first.
	 *
	 * @since 0.1
	 *
	 * @param string $localSiteId
	 *
	 * @return Site
	 */
	public function getSiteByLocalId( $localSiteId ) {
		return $this->offsetGet( $this->byLocalId[$localSiteId] );
	}

	/**
	 * Returns if the list contains the site with the provided global site identifier.
	 *
	 * @param string $globalSiteId
	 *
	 * @return boolean
	 */
	public function hasGlobalId( $globalSiteId ) {
		return array_key_exists( $globalSiteId, $this->byGlobalId );
	}

	/**
	 * Returns the Site with the provided global site id.
	 * The site needs to exist, so if not sure, call hasGlobalId first.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 *
	 * @return Site
	 */
	public function getSiteByGlobalId( $globalSiteId ) {
		return $this->offsetGet( $this->byGlobalId[$globalSiteId] );
	}

	/**
	 * Returns if the site list contains no sites.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->count() === 0;
	}

	/**
	 * Removes the site with the specified global id.
	 * The site needs to exist, so if not sure, call hasGlobalId first.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 */
	public function removeSiteByGlobalId( $globalSiteId ) {
		$this->offsetUnset( $this->byGlobalId[$globalSiteId] );
	}

}