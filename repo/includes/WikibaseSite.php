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
	protected $urlPath;
	protected $type;
	protected $filePath;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param string $id
	 * @param string $group
	 * @param string $url
	 * @param string $urlPath
	 * @param string $type
	 * @param string|boolean $filePath
	 */
	public function __construct( $id, $group, $url, $urlPath, $type = 'unknown', $filePath = false ) {
		$this->id = $id;
		$this->group = $group;
		$this->url = $url;
		$this->urlPath = $urlPath;
		$this->type = $type;
		$this->filePath = $filePath;
	}

	/**
	 * Creates and returns a new instance from an array with url, group, urlpath, type and filepath keys.
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
			$site['url'],
			$site['urlpath'],
			$site['type'],
			$site['filepath']
		);
	}

	/**
	 * Returns the sites identifier.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns the sites identifier.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Returns the sites group.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getGroup() {
		return $this->group;
	}

	/**
	 * Returns the sites type.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
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
	public function getPageUrl( $pageName = '' ) {
		return str_replace( '$1', $pageName, $this->url . $this->urlPath );
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
		if ( $this->filePath === false ) {
			return false;
		}

		return str_replace( '$1', $path, $this->url . $this->filePath );
	}

}