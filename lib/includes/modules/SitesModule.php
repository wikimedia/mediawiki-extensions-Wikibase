<?php

namespace Wikibase;

use ResourceLoaderContext;
use ResourceLoaderModule;
use SiteSQLStore;
use Wikibase\Lib\SitesModuleWorker;

/**
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
class SitesModule extends ResourceLoaderModule {

	/**
	 * @var SitesModuleWorker
	 */
	private $worker;

	public function __construct() {
		$this->worker = new SitesModuleWorker(
			Settings::singleton(),
			SiteSQLStore::newInstance()
		);
	}

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see ResourceLoaderModule::getScript
	 *
	 * @since 0.2
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		return $this->worker->getScript();
	}

	/**
	 * @see ResourceLoaderModule::getModifiedHash
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getModifiedHash( ResourceLoaderContext $context ) {
		return $this->worker->getModifiedHash();
	}

	/**
	 * @see ResourceLoaderModule::getModifiedTime
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return int
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		return $this->getHashMtime( $context );
	}

}
