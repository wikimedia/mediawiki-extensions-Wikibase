<?php

namespace Wikibase\Hook;

use OutputPage;
use Wikibase\OutputPageJsConfigBuilder;
use Wikibase\Settings;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigHookHandler {

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var OutputPageJsConfigBuilder
	 */
	protected $outputPageConfigBuilder;

	/**
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
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
		$rightsUrl = $this->settings->get( 'dataRightsUrl' );
		$rightsText = $this->settings->get( 'dataRightsText' );
		$badgeItems = $this->settings->get( 'badgeItems' );

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
