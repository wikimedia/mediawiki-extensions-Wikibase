<?php

namespace Wikibase\Hook;

use OutputPage;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\NamespaceUtils;
use Wikibase\OutputPageJsConfigBuilder;
use Wikibase\ParserOutputJsConfigBuilder;
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
	 *
	 * @return OutputPage
	 */
	public function handle( OutputPage $out, $isExperimental ) {
		$outputConfigVars = $this->buildConfigVars( $out, $isExperimental );

		$out->addJsConfigVars( $outputConfigVars );

		return true;
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

		$configVars = $this->outputPageConfigBuilder->build(
			$out,
			$rightsUrl,
			$rightsText,
			$isExperimental
		);

		return $configVars;
	}

}
