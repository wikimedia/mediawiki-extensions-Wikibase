<?php



class WikibaseLinks {

	protected $filePath;
	protected $urlPath;
	protected $rawArray;


	protected $groups;



	public function __construct( array $siteGroups, $pathDefault, $urlDefault ) {

		$sites = array();
		$groups = array();

		foreach ( $siteGroups as $groupName => $siteGroup ) {
			$groups[$groupName] = array_keys( $siteGroup['sites'] );

			foreach ( $siteGroup['sites'] as $identifier => $data ) {
				if ( !is_array( $data ) ) {
					$data = array( 'site' => $data );
				}

				if ( !array_key_exists( 'filepath', $data ) ) {

				}
			}
		}
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
	 * @param string|null $groupName
	 *
	 * @return array
	 */
	public static function getSiteIdentifiers( $groupName = null ) {
		if ( is_null( $groupName ) ) {
			return
		}
		else {

		}
	}

}