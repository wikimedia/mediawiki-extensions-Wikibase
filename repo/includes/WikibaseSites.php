<?php

/**
 * Interface to access sitelink related configuration. Right now this is read
 * from the configuration in WBSettings, but later on we might get this from the db.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class WikibaseSites {

	protected $sites = array();
	protected $groups = array();

	/**
	 * @since 0.1
	 *
	 * @param array $siteGroups
	 * @param string $globalPathDefault
	 * @param string $globalUrlDefault
	 * @param string $globalTypeDefault
	 */
	public function __construct( array $siteGroups, $globalPathDefault, $globalUrlDefault, $globalTypeDefault ) {
		foreach ( $siteGroups as $groupName => $siteGroup ) {
			$this->groups[$groupName] = array_keys( $siteGroup['sites'] );

			$pathDefault = array_key_exists( 'defaultSiteFilePath', $siteGroup ) ? $siteGroup['defaultSiteFilePath'] : $globalPathDefault;
			$urlDefault = array_key_exists( 'defaultSiteUrlPath', $siteGroup ) ? $siteGroup['defaultSiteUrlPath'] : $globalUrlDefault;
			$typeDefault = array_key_exists( 'defaultSiteType', $siteGroup ) ? $siteGroup['defaultSiteType'] : $globalUrlDefault;

			foreach ( $siteGroup['sites'] as $identifier => $data ) {
				if ( !is_array( $data ) ) {
					$data = array( 'site' => $data );
				}

				if ( !array_key_exists( 'filepath', $data ) ) {
					$data['filepath'] = $pathDefault;
				}

				if ( !array_key_exists( 'urlpath', $data ) ) {
					$data['urlpath'] = $urlDefault;
				}

				if ( !array_key_exists( 'type', $data ) ) {
					$data['type'] = $typeDefault;
				}

				$this->sites[$identifier] = $data;
			}
		}
	}

	/**
	 * Returns an instance of WikibaseSites.
	 *
	 * @since 0.1
	 *
	 * @return WikibaseSites
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new self(
				WBCSettings::get( 'siteIdentifiers' ),
				WBCSettings::get( 'defaultSiteUrlPath' ),
				WBCSettings::get( 'defaultSiteFilePath' ),
				WBCSettings::get( 'defaultSiteType' )
			);
		}

		return $instance;
	}

	/**
	 * @since 0.1
	 *
	 * @param string|null $groupName
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getIdentifiers( $groupName = null ) {
		if ( is_null( $groupName ) ) {
			if ( !array_key_exists( $groupName, $this->groups ) ) {
				throw new MWException( "No site group with name '$groupName' exists" );
			}

			return $this->groups[$groupName];
		}
		else {
			return array_keys( $this->sites );
		}
	}

	/**
	 * Returns the full url for the specified site.
	 * A page can also be provided, which is then added to the url.
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return false|string
	 */
	public function getUrl( $siteId, $pageName ) {
		if ( !array_key_exists( $siteId, $this->sites ) ) {
			return false;
		}

		return str_replace( '$1', $pageName, $this->sites[$siteId] );
	}

}