<?php

class WikibaseLinkGroup {

	protected $filePath;
	protected $urlPath;
	protected $links;



}

class WikibaseLinks {

	protected $filePath;
	protected $urlPath;
	protected $groups;

	protected $rawArray;

	public function __construct( array $linkGroups, $pathDefault, $urlDefault ) {
		$this->rawArray = $linkGroups;

	}

	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new self(
				WBCSettings::get( 'siteIdentifiers' ),
				WBCSettings::get( 'defaultSiteUrlPath' ),
				WBCSettings::get( 'defaultSiteFilePath' )
			);
		}

		return $instance;
	}

	/**
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getSiteIdentifiers() {

	}

}