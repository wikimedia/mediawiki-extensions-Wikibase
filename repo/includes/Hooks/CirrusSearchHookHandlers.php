<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use Content;
use Elastica\Document;
use ParserOutput;
use Title;
use Wikibase\EntityContent;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CirrusSearchHookHandlers {

	/**
	 * @var WikibaseFieldDefinitions
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
	 * @return BuildDocumentParserHookHandler
	 */
	public static function newFromGlobalState() {
		return new self(
			new WikibaseFieldDefinitions()
		);
	}

	/**
	 * @param WikibaseFieldDefinitions $fieldDefinitions
	 */
	public function __construct( WikibaseFieldDefinitions $fieldDefinitions ) {
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * @param Document $document
	 * @param Content $content
	 */
	public function indexExtraFields( Document $document, Content $content ) {
		if ( !$content instanceof EntityContent || $content->isRedirect() === true ) {
			return;
		}

		$fields = $this->fieldDefinitions->getFields();
		$entity = $content->getEntity();

		foreach ( $fields as $fieldName => $field ) {
			$data = $field->getFieldData( $entity );
			$document->set( $fieldName, $data );
		}
	}

	/**
	 * @param array &$config
	 */
	public function addExtraFieldsToMappingConfig( array &$config ) {
		$fields = $this->fieldDefinitions->getFields();

		foreach ( $fields as $fieldName => $field ) {
			$config['page']['properties'][$fieldName] = $field->getMapping();
		}
	}

}
