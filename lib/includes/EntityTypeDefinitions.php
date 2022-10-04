<?php

namespace Wikibase\Lib;

use InvalidArgumentException;

/**
 * Service that manages entity type definition. This is a registry that provides access to factory
 * functions for various services associated with entity types, such as serializers.
 *
 * EntityTypeDefinitions provides a one-stop interface for defining entity types.
 * Each entity type is defined using a "entity type definition" array.
 *
 * @see @ref docs_topics_entitytypes for the fields of a definition array
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class EntityTypeDefinitions {

	public const ENTITY_STORE_FACTORY_CALLBACK = 'entity-store-factory-callback';
	public const ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK = 'entity-revision-lookup-factory-callback';
	public const ENTITY_TITLE_STORE_LOOKUP_FACTORY_CALLBACK = 'entity-title-store-lookup-factory-callback';
	public const ENTITY_METADATA_ACCESSOR_CALLBACK = 'entity-metadata-accessor-callback';
	public const PREFETCHING_TERM_LOOKUP_CALLBACK = 'prefetching-term-lookup-callback';
	public const SERIALIZER_FACTORY_CALLBACK = 'serializer-factory-callback';
	public const STORAGE_SERIALIZER_FACTORY_CALLBACK = 'storage-serializer-factory-callback';
	public const DESERIALIZER_FACTORY_CALLBACK = 'deserializer-factory-callback';
	public const VIEW_FACTORY_CALLBACK = 'view-factory-callback';
	public const META_TAGS_CREATOR_CALLBACK = 'meta-tags-creator-callback';
	public const CONTENT_MODEL_ID = 'content-model-id';
	public const CONTENT_HANDLER_FACTORY_CALLBACK = 'content-handler-factory-callback';
	public const ENTITY_FACTORY_CALLBACK = 'entity-factory-callback';
	public const ENTITY_DIFFER_STRATEGY_BUILDER = 'entity-differ-strategy-builder';
	public const ENTITY_PATCHER_STRATEGY_BUILDER = 'entity-patcher-strategy-builder';
	public const ENTITY_DIFF_VISUALIZER_CALLBACK = 'entity-diff-visualizer-callback';
	public const JS_DESERIALIZER_FACTORY_FUNCTION = 'js-deserializer-factory-function';
	public const ENTITY_ID_COMPOSER_CALLBACK = 'entity-id-composer-callback';
	public const CHANGEOP_DESERIALIZER_CALLBACK = 'changeop-deserializer-callback';
	public const RDF_BUILDER_FACTORY_CALLBACK = 'rdf-builder-factory-callback';
	public const RDF_BUILDER_STUB_FACTORY_CALLBACK = 'rdf-builder-stub-factory-callback';
	public const ENTITY_SEARCH_CALLBACK = 'entity-search-callback';
	public const SUB_ENTITY_TYPES = 'sub-entity-types';
	public const LINK_FORMATTER_CALLBACK = 'link-formatter-callback';
	public const ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK = 'entity-id-html-link-formatter-callback';
	public const ENTITY_REFERENCE_EXTRACTOR_CALLBACK = 'entity-reference-extractor-callback';
	public const FULLTEXT_SEARCH_CONTEXT = 'fulltext-search-context';
	public const SEARCH_FIELD_DEFINITIONS = 'search-field-definitions';
	public const RDF_LABEL_PREDICATES = 'rdf-builder-label-predicates';
	public const LUA_ENTITY_MODULE = 'lua-entity-module';
	public const ENTITY_ID_LOOKUP_CALLBACK = 'entity-id-lookup-callback';
	public const ARTICLE_ID_LOOKUP_CALLBACK = 'article-id-lookup-callback';
	public const TITLE_TEXT_LOOKUP_CALLBACK = 'title-text-lookup-callback';
	public const URL_LOOKUP_CALLBACK = 'url-lookup-callback';
	public const EXISTENCE_CHECKER_CALLBACK = 'existence-checker-callback';
	public const REDIRECT_CHECKER_CALLBACK = 'redirect-checker-callback';
	public const ENTITY_ID_PATTERN = 'entity-id-pattern';
	public const ENTITY_ID_BUILDER = 'entity-id-builder';
	public const PROPERTY_DATA_TYPE_LOOKUP_CALLBACK = 'property-data-type-lookup-callback';

	/**
	 * @var array[]
	 */
	private $entityTypeDefinitions;

	/**
	 * @param array[] $entityTypeDefinitions Map from entity types to entity definitions
	 *        See class level documentation for details
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $entityTypeDefinitions ) {
		foreach ( $entityTypeDefinitions as $type => $def ) {
			if ( !is_string( $type ) || !is_array( $def ) ) {
				throw new InvalidArgumentException( '$entityTypeDefinitions must be a map from string to arrays' );
			}
		}

		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @return string[] a list of all defined entity types
	 */
	public function getEntityTypes() {
		return array_keys( $this->entityTypeDefinitions );
	}

	/**
	 * @param string $field one of the constants declared in this class
	 *
	 * @return array map of entity types to the values specified for the given field.
	 *         Not guaranteed to contain all entity types.
	 */
	public function get( string $field ): array {
		$fieldValues = [];

		foreach ( $this->entityTypeDefinitions as $type => $def ) {
			if ( isset( $def[$field] ) ) {
				$fieldValues[$type] = $def[$field];
			}
		}

		return $fieldValues;
	}

	/**
	 * @return callable[]
	 */
	public function getEntityIdBuilders() {
		$result = [];

		foreach ( $this->entityTypeDefinitions as $def ) {
			if ( isset( $def[self::ENTITY_ID_PATTERN] ) && isset( $def[self::ENTITY_ID_BUILDER] ) ) {
				$result[$def[self::ENTITY_ID_PATTERN]] = $def[self::ENTITY_ID_BUILDER];
			}
		}

		return $result;
	}

}
