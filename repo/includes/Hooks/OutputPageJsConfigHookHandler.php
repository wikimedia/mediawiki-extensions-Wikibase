<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\OutputPageJsConfigBuilder;
use Wikibase\SettingsArray;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigHookHandler {

	/**
	 * @var SettingsArray
	 */
	protected $settings;

	/**
	 * @var OutputPageJsConfigBuilder
	 */
	protected $outputPageConfigBuilder;

	/**
	 * @todo: don't pass around SettingsArray, just take specific constructor params.
	 *
	 * @param SettingsArray $settings
	 */
	public function __construct( SettingsArray $settings ) {
		$this->settings = $settings;
		$this->outputPageConfigBuilder = new OutputPageJsConfigBuilder();
	}

	/**
	 * @param OutputPage &$out
	 * @param boolean $isExperimental
	 */
	public function handle( OutputPage $out, $isExperimental ) {
		$outputConfigVars = $this->buildConfigVars( $out, $isExperimental );

		$out->addJsConfigVars( $outputConfigVars );
	}

	/**
	 * @param OutputPage $out
	 * @param boolean $isExperimental
	 *
	 * @return array
	 */
	private function buildConfigVars( OutputPage $out, $isExperimental ) {
		$rightsUrl = $this->settings->getSetting( 'dataRightsUrl' );
		$rightsText = $this->settings->getSetting( 'dataRightsText' );
		$badgeItems = $this->settings->getSetting( 'badgeItems' );

		$configVars = $this->outputPageConfigBuilder->build(
			$out,
			$rightsUrl,
			$rightsText,
			$badgeItems,
			$isExperimental
		);

		return $configVars;
	}

}
