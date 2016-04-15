<?php

namespace Wikibase\Repo\Modules;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Xml;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MwConfigModule extends ResourceLoaderModule {

	/**
	 * @var string
	 */
	private $configName;

	/**
	 * @var callable
	 */
	private $getWorker;

	public function __construct( $info ) {
		$this->getWorker = $info['getworker'];
		$this->configName = $info['name'];
	}

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @since 0.5
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		return Xml::encodeJsCall(
			'mediaWiki.config.set',
			[
				$this->configName,
				call_user_func( $this->getWorker )->getValue( $context )
			],
			ResourceLoader::inDebugMode()
		);
	}

	/**
	 * @return bool
	 */
	public function enableModuleContentVersion() {
		return true;
	}

}
