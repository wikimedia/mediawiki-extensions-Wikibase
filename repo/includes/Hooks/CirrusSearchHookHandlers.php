<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use Content;
use Elastica\Document;
use ParserOutput;
use Title;
use Wikibase\EntityContent;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\SearchFieldDefinitionsBuilder;
use Wikibase\Repo\Search\Elastic\Indexer\EntityContentIndexer;
use Wikibase\Repo\Search\Elastic\Indexer\ItemIndexer;
use Wikibase\Repo\Search\Elastic\Indexer\PropertyIndexer;
use Wikibase\Repo\Search\Elastic\Mapping\MappingConfigModifier;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CirrusSearchHookHandlers {

	/**
	 * @var MappingConfigModifier
	 */
	private $mappingConfigModifier;

	/**
	 * @var EntityContentIndexer
	 */
	private $entityContentIndexer;

	/**
	 * @param Document $document
	 * @param Title $title
	 * @param Content $content
	 * @param ParserOutput $parserOutput
	 * @param Connection $connection
	 *
	 * @return bool
	 */
	public static function onCirrusSearchBuildDocumentParse(
		Document $document,
		Title $title,
		Content $content,
		ParserOutput $parserOutput,
		Connection $connection
	) {
		$hookHandler = self::newFromGlobalState();
		$hookHandler->indexExtraFields( $content, $document );

		return true;
	}

	/**
	 * @param array &$config
	 * @param MappingConfigBuilder $mappingConfigBuilder
	 *
	 * @return bool
	 */
	public static function onCirrusSearchMappingConfig(
		array &$config,
		MappingConfigBuilder $mappingConfigBuilder
	) {
		$handler = self::newFromGlobalState();
		$handler->addExtraFieldsToMappingConfig( $config );

		return true;
	}

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		$contentLanguages = new MediaWikiContentLanguages();
		$languageCodes = $contentLanguages->getLanguages();

		$fieldDefinitionsBuilder = new SearchFieldDefinitionsBuilder();

		$fieldDefinitions = [
			'item' => $fieldDefinitionsBuilder->newItemFieldDefinitions( $languageCodes ),
			'property' => $fieldDefinitionsBuilder->newPropertyFieldDefinitions( $languageCodes )
		];

		// @todo get the list of known fields from the Mapping
		// when there is a new or unknown language code, then log that somewhere and then
		// the mapping needs to be updated, since possible fields are defined explicitly.
		$entityIndexers = [
			'item' => new ItemIndexer( $languageCodes ),
			'property' => new PropertyIndexer( $languageCodes )
		];

		return new self(
			new MappingConfigModifier( $fieldDefinitions ),
			new EntityContentIndexer( $entityIndexers )
		);
	}

	/**
	 * @param MappingConfigModifier $mappingConfigModifier
	 * @param EntityContentIndexer $entityContentIndexr
	 */
	public function __construct(
		MappingConfigModifier $mappingConfigModifier,
		EntityContentIndexer $entityContentIndexer
	) {
		$this->mappingConfigModifier = $mappingConfigModifier;
		$this->entityContentIndexer = $entityContentIndexer;
	}

	/**
	 * @param Document $document
	 * @param Content $content
	 */
	public function indexExtraFields( Content $content, Document $document ) {
		if ( !$content instanceof EntityContent || $content->isRedirect() === true ) {
			return;
		}

		$this->entityContentIndexer->indexContent( $content, $document );
	}

	/**
	 * @param array &$config
	 */
	public function addExtraFieldsToMappingConfig( array &$config ) {
		$this->mappingConfigModifier->addFields( $config['page']['properties'] );
	}

}
