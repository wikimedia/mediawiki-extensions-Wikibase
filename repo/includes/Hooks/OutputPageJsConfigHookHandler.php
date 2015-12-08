<?php

namespace Wikibase\Repo\Hooks;

use OutputPage;
use Wikibase\OutputPageJsConfigBuilder;
use Wikibase\Repo\EntityNamespaceLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class OutputPageJsConfigHookHandler {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var OutputPageJsConfigBuilder
	 */
	private $outputPageConfigBuilder;

	/**
	 * @var string
	 */
	private $dataRightsUrl;

	/**
	 * @var string
	 */
	private $dataRightsText;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string $dataRightsUrl
	 * @param string $dataRightsText
	 * @param string[] $badgeItems
	 */
	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		$dataRightsUrl,
		$dataRightsText,
		array $badgeItems
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->outputPageConfigBuilder = new OutputPageJsConfigBuilder();
		$this->dataRightsUrl = $dataRightsUrl;
		$this->dataRightsText = $dataRightsText;
		$this->badgeItems = $badgeItems;
	}

	/**
	 * @return self
	 */
	private static function newFromGlobalState() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$settings = $wikibaseRepo->getSettings();

		return new self(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' ),
			$settings->getSetting( 'badgeItems' )
		);
	}

	/**
	 * Puts user-specific and other vars that we don't want stuck
	 * in parser cache (e.g. copyright message)
	 *
	 * @param OutputPage $out
	 * @param string &$html
	 *
	 * @return bool
	 */
	public static function onOutputPageBeforeHtmlRegisterConfig( OutputPage $out, &$html ) {
		$instance = self::newFromGlobalState();
		return $instance->doOutputPageBeforeHtmlRegisterConfig( $out );
	}

	/**
	 * @param OutputPage $out
	 * @param string &$html
	 *
	 * @return bool
	 */
	public function doOutputPageBeforeHtmlRegisterConfig( OutputPage $out ) {
		if ( !$this->entityNamespaceLookup->isEntityNamespace( $out->getTitle()->getNamespace() ) ) {
			return true;
		}

		$this->handle( $out );

		return true;
	}

	/**
	 * @param OutputPage &$out
	 */
	private function handle( OutputPage $out ) {
		$outputConfigVars = $this->buildConfigVars( $out );

		$out->addJsConfigVars( $outputConfigVars );
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return array
	 */
	private function buildConfigVars( OutputPage $out ) {
		return $this->outputPageConfigBuilder->build(
			$out,
			$this->dataRightsUrl,
			$this->dataRightsText,
			$this->badgeItems
		);
	}

}
