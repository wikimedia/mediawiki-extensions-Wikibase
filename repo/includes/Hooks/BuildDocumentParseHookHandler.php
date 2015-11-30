<?php

namespace Wikibase\Repo\Hooks;

use CirrusSearch\Connection;
use Content;
use Elastica\Document;
use ParserOutput;
use Title;
use Wikibase\EntityContent;
use Wikibase\Repo\Search\Fields\WikibaseFieldsDefinition;

/**
 * Extension hooks
 */
class BuildDocumentParseHookHandler {

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

}
