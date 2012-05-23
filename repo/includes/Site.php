<?php

namespace Wikibase;

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
 * @author Daniel Werner
 */
class Site {

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
	 * @param string $url base url to the site
	 * @param string $urlPath relative path added to $url, can contain a placeholder '$1' for a page on that site
	 * @param string $type type of the site, for example 'mediawiki'
	 * @param string|boolean $filePath relative path added to $url
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
	 * @return Site
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
	 * Returns the sites url.
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
	 * @param string $pageName will be encoded internally
	 *
	 * @return string
	 */
	public function getPageUrl( $pageName = '' ) {
		return str_replace( '$1', rawurlencode( $pageName ), $this->getPageUrlPath() );
	}

	/**
	 * Returns the path to the url of a page where the page is represented by a replacement marker '$1'.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getPageUrlPath() {
		return $this->url . $this->urlPath;
	}

	/**
	 * Returns the relative path to the url of a page where the page is represented by a replacement marker '$1'.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getRelativePageUrlPath() {
		return $this->urlPath;
	}

	/**
	 * Returns the relative path to the url of a page where the page is represented by a replacement marker '$1'.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getRelativeFilePath() {
		return $this->filePath;
	}

	/**
	 * Returns the full path for the specified site.
	 * A path can also be provided, which is then added to the base path.
	 *
	 * @since 0.1
	 *
	 * @param string $path has to be url encoded where required, no further encoding will be done
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