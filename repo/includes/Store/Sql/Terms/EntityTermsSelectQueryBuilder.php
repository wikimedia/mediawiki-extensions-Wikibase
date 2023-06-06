<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 */
class EntityTermsSelectQueryBuilder extends SelectQueryBuilder {

	private string $entityTermsTable;
	private string $entityTermsJoinColumn;
	private string $entityIdColumn;

	public function __construct( IReadableDatabase $db, string $entityType ) {
		parent::__construct( $db );

		if ( $entityType === Item::ENTITY_TYPE ) {
			$this->entityTermsTable = 'wbt_item_terms';
			$this->entityTermsJoinColumn = 'wbit_term_in_lang_id';
			$this->entityIdColumn = 'wbit_item_id';
		} elseif ( $entityType === Property::ENTITY_TYPE ) {
			$this->entityTermsTable = 'wbt_property_terms';
			$this->entityTermsJoinColumn = 'wbpt_term_in_lang_id';
			$this->entityIdColumn = 'wbpt_property_id';
		} else {
			throw new InvalidArgumentException( "'{$entityType}' is not supported." );
		}

		$this->select( $this->entityIdColumn )
			->from( $this->entityTermsTable )
			->join( 'wbt_term_in_lang', null, "{$this->entityTermsJoinColumn}=wbtl_id" )
			->join( 'wbt_text_in_lang', null, 'wbtl_text_in_lang_id=wbxl_id' )
			->join( 'wbt_text', null, 'wbxl_text_id=wbx_id' );
	}

	private function getTermInLanguageCondition( int $typeId, string $language, string $text ): array {
		return [
			'wbtl_type_id' => $typeId,
			'wbxl_language' => $language,
			'wbx_text' => $text,
		];
	}

	public function whereTerm( int $typeId, string $lang, string $text ): self {
		return $this->where( $this->getTermInLanguageCondition( $typeId, $lang, $text ) );
	}

	public function whereMultiTerm( int $typeId, array $languages, array $texts ): self {
		$conditions = [];

		for ( $i = 0; $i < count( $languages ); $i++ ) {
			$language = $languages[$i];
			$text = $texts[$i];

			$conditions[] = $this->getTermInLanguageCondition( $typeId, $language, $text );
		}

		$labelStatements = [];
		foreach ( $conditions as $condition ) {
			$labelStatements[] = $this->db->makeList( $condition, $this->db::LIST_AND );
		}

		return $this->where( $this->db->makeList( $labelStatements, $this->db::LIST_OR ) );
	}

	public function getEntityIdColumn(): string {
		return $this->entityIdColumn;
	}

}
