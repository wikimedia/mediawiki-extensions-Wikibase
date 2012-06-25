<?php

namespace Wikibase;

/**
 * Represents the sites database table.
 * All access to this table should be done through this class.
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
class SitesTable extends \ORMTable {

	/**
	 * @see IORMTable::getName()
	 * @since 0.1
	 * @return string
	 */
	public function getName() {
		return 'sites';
	}

	/**
	 * @see IORMTable::getFieldPrefix()
	 * @since 0.1
	 * @return string
	 */
	public function getFieldPrefix() {
		return 'site_';
	}

	/**
	 * @see IORMTable::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	public function getRowClass() {
		return '\Wikibase\SiteRow';
	}

	/**
	 * @see IORMTable::getFields()
	 * @since 0.1
	 * @return array
	 */
	public function getFields() {
		return array(
			'id' => 'id',

			// Site data
			'global_key' => 'str',
			'type' => 'int',
			'group' => 'int',
			'url' => 'str',
			'page_path' => 'str',
			'file_path' => 'str',
			'language' => 'str',
			'data' => 'blob',

			// Site config
			'local_key' => 'str',
			'link_inline' => 'bool',
			'link_navigation' => 'bool',
			'forward' => 'bool',
			'config' => 'blob',
		);
	}

	/**
	 * Returns the class name for the provided site type.
	 *
	 * @since 0.1
	 *
	 * @param integer $siteType
	 *
	 * @return string
	 */
	protected static function getClassForType( $siteType ) {
		global $wgSiteTypes;
		return array_key_exists( $siteType, $wgSiteTypes ) ? $wgSiteTypes[$siteType] : 'Wikibase\SiteRow';
	}

	/**
	 * Factory method to construct a new WikibaseChange instance.
	 *
	 * @since 0.1
	 *
	 * @param array $data
	 * @param boolean $loadDefaults
	 *
	 * @return Change
	 * @throws MWException
	 */
	public function newFromArray( array $data, $loadDefaults = false ) {
		if ( !array_key_exists( 'type', $data ) ) {
			throw new MWException( 'The type element must be set in the $data array before a new change can be constructed.' );
		}

		$class = static::getClassForType( $data['type'] );

		return new $class( $this, $data, $loadDefaults );
	}

}