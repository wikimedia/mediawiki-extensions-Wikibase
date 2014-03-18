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
	 * @var EntityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	protected $parserOutputConfigBuilder;

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var OutputPageJsConfigBuilder
	 */
	protected $outputPageConfigBuilder;

	/**
	 * @param EntityContentFactory $entityContentFactory
	 * @param Settings $settings
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 * @param array $langCodes
	 */
	public function __construct(
		EntityContentFactory $entityContentFactory,
		ParserOutputJsConfigBuilder $parserOutputConfigBuilder,
		Settings $settings,
		array $langCodes
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->parserOutputConfigBuilder = $parserOutputConfigBuilder;
		$this->settings = $settings;
		$this->langCodes = $langCodes;

		$this->outputPageConfigBuilder = new OutputPageJsConfigBuilder();
	}

	/**
	 * @param OutputPage &$out
	 * @param boolean $isExperimental
	 *
	 * @return OutputPage
	 */
	public function handle( OutputPage $out, $isExperimental ) {
		$configVars = $out->getJsConfigVars();

		// backwards compatibility, in case config is not in parser cache and output page
		if ( !$this->hasParserConfigVars( $configVars ) ) {
			// gets from parser cache, with fallback to generate it if not cached
			$revisionId = $out->getRevisionId();
			$parserConfigVars = $this->getParserConfigVars( $revisionId );
			$out->addJsConfigVars( $parserConfigVars );
		}

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

	/**
	 * @param mixed
	 *
	 * @return boolean
	 */
	private function hasParserConfigVars( $configVars ) {
		return is_array( $configVars ) && array_key_exists( 'wbEntityId', $configVars );
	}

	/**
	 * @param int $revisionId
	 *
	 * @return array
	 */
	private function getParserConfigVars( $revisionId ) {
		$entityContent = $this->entityContentFactory->getFromRevision( $revisionId );

		if ( $entityContent === null || ! $entityContent instanceof \Wikibase\EntityContent ) {
			// entity or revision deleted, or non-entity content in entity namespace
			wfDebugLog( __CLASS__, "No entity content found for revision $revisionId" );
			return array();
		}

		return $this->buildParserConfigVars( $entityContent );
	}

	/**
	 * @param EntityContent $entityContent
	 *
	 * @return array
	 */
	private function buildParserConfigVars( EntityContent $entityContent ) {
		$options = $this->makeSerializationOptions();

		$entity = $entityContent->getEntity();

		$parserConfigVars = $this->parserOutputConfigBuilder->build(
			$entity,
			$options
		);

		return $parserConfigVars;
	}

	/**
	 * @return SerializationOptions
	 */
	private function makeSerializationOptions() {
		$options = new SerializationOptions();
		$options->setLanguages( $this->langCodes );

		return $options;
	}

}
