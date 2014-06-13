<?php

namespace Wikibase\Hook;

use OutputPage;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\ParserOutputJsConfigBuilder;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MakeGlobalVariablesScriptHandler {

	/**
	 * @var EntityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	protected $parserOutputConfigBuilder;

	/**
	 * @param EntityContentFactory $entityContentFactory
	 * @param ParserOutputJsConfigBuilder $parserOutputConfigBuilder
	 * @param array $langCodes
	 */
	public function __construct(
		EntityContentFactory $entityContentFactory,
		ParserOutputJsConfigBuilder $parserOutputConfigBuilder,
		array $langCodes
	) {
		$this->entityContentFactory = $entityContentFactory;
		$this->parserOutputConfigBuilder = $parserOutputConfigBuilder;
		$this->langCodes = $langCodes;
	}

	/**
	 * @param OutputPage $out
	 */
	public function handle( OutputPage $out ) {
		$configVars = $out->getJsConfigVars();

		// backwards compatibility, in case config is not in parser cache and output page
		if ( !$this->hasParserConfigVars( $configVars ) ) {
			$this->addConfigVars( $out );
		}
	}

	/**
	 * Regenerates and adds parser config variables (e.g. wbEntity), in case
	 * they are missing in OutputPage (e.g. not in parser cache)
	 *
	 * @param OutputPage $out
	 */
	private function addConfigVars( OutputPage $out ) {
		$revisionId = $out->getRevisionId();

		// this will not be set for deleted pages
		if ( !$revisionId ) {
			return;
		}

		$parserConfigVars = $this->getParserConfigVars( $revisionId );
		$out->addJsConfigVars( $parserConfigVars );
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

		if ( $entityContent->isRedirect() ) {
			return array();
		} else {
			$entity = $entityContent->getEntity();
			return $this->buildParserConfigVars( $entity );
		}
	}

	/**
	 * @param Entity $entity
	 *
	 * @return array
	 */
	private function buildParserConfigVars( Entity $entity ) {
		$options = $this->makeSerializationOptions();

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
