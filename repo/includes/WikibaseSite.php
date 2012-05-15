<?php

/**
 * Class representing a single site that can be linked to.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseSite {

	protected $id;
	protected $group;
	protected $url;
	protected $type;
	protected $path;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $id
	 * @param string $group
	 * @param string $url
	 * @param string $type
	 * @param string|boolean $path
	 */
	public function __construct( $id, $group, $url, $type = 'unknown', $path = false ) {
		$this->id = $id;
		$this->group = $group;
		$this->url = $url;
		$this->type = $type;
		$this->path = $path;
	}

	/**
	 * Creates and returns a new instance from an array with group, urlpath, type and filepath keys.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param array $site
	 *
	 * @return WikibaseSite
	 */
	public static function newFromArray( $siteId, array $site ) {
		return new static(
			$siteId,
			$site['group'],
			$site['urlpath'],
			$site['type'],
			$site['filepath']
		);
	}

	/**
	 * Returns the full url for the specified site.
	 * A page can also be provided, which is then added to the url.
	 *
	 * @since 0.1
	 *
	 * @param string $pageName
	 *
	 * @return string
	 */
	public function getUrl( $pageName = '' ) {
		return str_replace( '$1', $pageName, $this->url );
	}

	/**
	 * Returns the full path for the specified site.
	 * A path can also be provided, which is then added to the base path.
	 *
	 * @since 0.1
	 *
	 * @param string $path
	 *
	 * @return false|string
	 */
	public function getPath( $path = '' ) {
		if ( $this->path === false ) {
			return false;
		}

		return str_replace( '$1', $path, $this->path['filepath'] );
	}

}