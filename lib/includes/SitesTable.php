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
			'global_key' => 'str',
			'type' => 'int',
			'group' => 'int',
			'url' => 'str',
			'page_path' => 'str',
			'file_path' => 'str',
			'local_key' => 'str',
			'link_inline' => 'bool',
			'link_navigation' => 'bool',
			'forward' => 'bool',
			'allow_transclusion' => 'bool',
		);
	}

}