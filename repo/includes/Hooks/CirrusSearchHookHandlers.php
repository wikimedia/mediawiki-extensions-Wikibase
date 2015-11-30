<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Connection;
use CirrusSearch\Maintenance\MappingConfigBuilder;
use Content;
use Elastica\Document;
use ParserOutput;
use Title;
use Wikibase\EntityContent;
use Wikibase\Repo\Search\Fields\WikibaseFieldsDefinition;

class CirrusSearchHookHandlers {

	/**
	 * @var WikibaseFieldsDefinition
	 */
	private $fieldsDefinition;

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
        $handler->addExtraFields( $config );

        return true;
    }

	/**
	 * @return BuildDocumentParserHookHandler
	 */
	public static function newFromGlobalState() {
		return new self(
			new WikibaseFieldsDefinition()
		);
	}

	/**
	 * @param WikibaseFieldsDefinition $fieldsDefinition
	 */
	public function __construct( WikibaseFieldsDefinition $fieldsDefinition ) {
		$this->fieldsDefinition = $fieldsDefinition;
	}

	/**
	 * @param Document $document
	 * @param Content $content
	 */
	public function indexExtraFields( Document $document, Content $content ) {
		if ( !$content instanceof EntityContent || $content->isRedirect() === true ) {
			return;
		}

		$fields = $this->fieldsDefinition->getFields();
		$entity = $content->getEntity();

		foreach ( $fields as $fieldName => $field ) {
			$data = $field->buildData( $entity );
			$document->set( $fieldName, $data );
		}
	}

	/**
	 * @param array &$config
	 */
	public function addExtraFields( array &$config ) {
		$fields = $this->fieldsDefinition->getFields();

		foreach ( $fields as $fieldName => $field ) {
			$config['page']['properties'][$fieldName] = $field->getMapping();
		}
	}

}
