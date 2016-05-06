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
		$hookHandler->indexExtraFields( $document, $content );

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

		return new self( $fieldDefinitions );
	}

	/**
	 * @param FieldDefinitions[] $fieldDefinitions
	 */
	public function __construct( array $fieldDefinitions ) {
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * @param Document $document
	 * @param Content $content
	 *
	 * @throws UnexpectedValueException
	 */
	public function indexExtraFields( Document $document, Content $content ) {
		if ( !$content instanceof EntityContent || $content->isRedirect() === true ) {
			return;
		}

		$entity = $content->getEntity();
		$entityType = $entity->getType();

		if ( !array_key_exists( $entityType, $this->fieldDefinitions ) ) {
			throw new UnexpectedValueException( 'Unexpected entity type: ' . $entityType );
		}

		$this->fieldDefinitions[$entityType]->indexEntity( $entity, $document );
	}

	/**
	 * @param array &$config
	 */
	public function addExtraFieldsToMappingConfig( array &$config ) {
		foreach ( $this->fieldDefinitions as $fieldDefinition ) {
			$properties = $fieldDefinition->getMappingProperties();
			$propertiesToAdd = array_diff_key( $properties, $config['page']['properties'] );

			foreach ( $propertiesToAdd as $key => $property ) {
				$config['page']['properties'][$key] = $property;
			}
		}
	}

}
