<?php

namespace Wikibase\Lib\Modules;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;

/**
 * Generic, reusable ResourceLoader module to set a JavaScript configuration variable via
 * mw.config.set.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiConfigModule extends RL\Module {

	/**
	 * @var string[]
	 */
	protected $targets;

	/**
	 * @var callable
	 */
	private $getConfigValueProvider;

	/**
	 * @param array $options ResourceLoader module options. Must include a "getconfigvalueprovider"
	 *  callable that returns a MediaWikiConfigValueProvider when called.
	 *  May include 'targets'. No other options supported yet.
	 */
	public function __construct( array $options ) {
		$this->getConfigValueProvider = $options['getconfigvalueprovider'];
		$this->targets = $options['targets'] ?? [ 'desktop', 'mobile' ];
	}

	/**
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 *
	 * @return string JavaScript code
	 */
	public function getScript( RL\Context $context ) {
		/** @var MediaWikiConfigValueProvider $configValueProvider */
		$configValueProvider = call_user_func( $this->getConfigValueProvider );

		return ResourceLoader::makeConfigSetScript( [
			$configValueProvider->getKey() => $configValueProvider->getValue(),
		] );
	}

	/**
	 * @return bool Always true.
	 */
	public function enableModuleContentVersion() {
		return true;
	}

}
