<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use Content;
use Elastica\Document;
use ParserOutput;
use Title;
use UnexpectedValueException;
use Wikibase\EntityContent;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\FieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinitions;
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
	 * @var FieldDefinitions[]
	 */
	private $fieldDefinitions;

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

		// @todo get these from EntityTypeDefinitions
		$fieldDefinitions = [
			'item' => new ItemFieldDefinitions( $languageCodes ),
			'property' => new PropertyFieldDefinitions( $languageCodes )
		];

		$entityIndexers = [
			'item' => new ItemIndexer( $languageCodes ),
			'property' => new PropertyIndexer( $languageCodes )
		];

		return new self(
			new MappingConfigModifier(),
			new EntityContentIndexer( $entityIndexers ),
			$fieldDefinitions
		);
	}

	/**
	 * @param MappingConfigModifier $mappingConfigModifier
	 * @param EntityContentIndexer $entityContentIndexr
	 * @param FieldDefinitions[] $fieldDefinitions
	 */
	public function __construct(
		MappingConfigModifier $mappingConfigModifier,
		EntityContentIndexer $entityContentIndexer,
		array $fieldDefinitions
	) {
		$this->mappingConfigModifier = $mappingConfigModifier;
		$this->entityContentIndexer = $entityContentIndexer;
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * @param Document $document
	 * @param Content $content
	 *
	 * @throws UnexpectedValueException
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
		$this->mappingConfigModifier->addProperties(
			$this->fieldDefinitions,
			$config['page']['properties']
		);
	}

}
