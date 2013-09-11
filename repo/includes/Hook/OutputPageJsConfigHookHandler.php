<?php

namespace Wikibase\Hook;

use MWException;
use OutputPage;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityContentFactory;
use Wikibase\LanguageFallbackChainFactory;
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
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @var EntityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	protected $fallbackChainFactory;

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
	 * @param EntityIdParser $idParser
	 * @param EntityContentFactory $entityContentFactory
	 * @param LanguageFallbackChainFactory $fallbackChainFactory
	 * @param Settings $settings
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 */
	public function __construct(
		EntityIdParser $idParser,
		EntityContentFactory $entityContentFactory,
		LanguageFallbackChainFactory $fallbackChainFactory,
		ParserOutputJsConfigBuilder $parserOutputConfigBuilder,
		Settings $settings
	) {
		$this->idParser = $idParser;
		$this->entityContentFactory = $entityContentFactory;
		$this->fallbackChainFactory = $fallbackChainFactory;
		$this->parserOutputConfigBuilder = $parserOutputConfigBuilder;
		$this->settings = $settings;

		$this->outputPageConfigBuilder = new OutputPageJsConfigBuilder();
	}

	/**
	 * @return boolean
	 */
	public function handle( OutputPage $out ) {
		// gets from parser cache, with fallback to generate it
		$parserOutputConfigVars = $this->getParserOutputConfigVars( $out );

		if ( !$this->hasEntityIdParamInConfig( $parserOutputConfigVars ) ) {
			// entity might be deleted
			return true;
		}

		try {
			$entityId = $this->idParser->parse( $parserOutputConfigVars['wbEntityId'] );
		} catch ( EntityIdParsingException $ex ) {
			wfWarn( 'Invalid entity id in parser output config vars: ' . $ex->getMessage() );
			$revisionId = $out->getRevisionId();

			try {
				$entityId = $this->loadEntityIdFromRevisionId( $revisionId );
			} catch ( MWException $ex ) {
				wfWarn( $ex->getMessage() );
				return true;
			}
		}

		$configVars = $this->buildConfigVars( $out, $entityId );

		$this->registerConfigVars( $out, $configVars );

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param EntityId $entityId
	 *
	 * @return array
	 */
	private function buildConfigVars( OutputPage $out, EntityId $entityId ) {
		$rightsUrl = $this->settings->get( 'dataRightsUrl' );
		$rightsText = $this->settings->get( 'dataRightsText' );

		$configVars = $this->outputPageConfigBuilder->build( $out, $entityId, $rightsUrl, $rightsText );

		return $configVars;
	}

	/**
	 * @param OutputPage $out
	 * @param array $configVars
	 */
	private function registerConfigVars( OutputPage $out, array $configVars ) {
		foreach( $configVars as $key => $configVar ) {
			$out->addJsConfigVars( $key, $configVar );
		}
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return array
	 */
	private function getParserOutputConfigVars( OutputPage $out ) {
		$configVars = $out->getJsConfigVars();

		if ( !$this->hasEntityIdParamInConfig( $configVars ) ) {
			$parserConfigVars = $this->getParserConfigVars( $out );
			$this->registerConfigVars( $out, $parserConfigVars );
			$configVars = $out->getJsConfigVars();
		}

		return $configVars;
	}

	/**
	 * @param mixed
	 *
	 * @return boolean
	 */
	private function hasEntityIdParamInConfig( $configVars ) {
		return is_array( $configVars ) && array_key_exists( 'wbEntityId', $configVars );
	}

	/**
	 * @param OutputPage $out
	 */
	private function getParserConfigVars( OutputPage $out ) {
		$context = $out->getContext();
		$langCode = $context->getLanguage()->getCode();

		$revisionId = $out->getRevisionId();
		$entityContent = $this->entityContentFactory->getFromRevision( $revisionId );

		if ( !$entityContent ) {
			// entity or revision deleted?
			wfDebugLog( __CLASS__, "No entity content found for revision $revisionId" );
			return array();
		}

		$entity = $entityContent->getEntity();

		$fallbackChain = $this->fallbackChainFactory->newFromContextForPageView( $context );
		$options = $entityContent->makeSerializationOptions( $langCode, $fallbackChain );

		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;

		return $this->parserOutputConfigBuilder->build( $entity, $options, $isExperimental );
	}

	/**
	 * @param int $revisionId
	 *
	 * @throws MWException
	 * @return EntityId
	 */
	private function loadEntityIdFromRevisionId( $revisionId ) {
		$entityContent = $this->entityContentFactory->getFromRevision( $revisionId );

		if ( !$entityContent ) {
			throw new MWException( "No entity content found for $revisionId" );
		}

		$entityId = $entityContent->getEntity()->getId();

		if ( !$entityId ) {
			throw new MWException( 'No entity id found' );
		}

		return $entityId;
	}

}
