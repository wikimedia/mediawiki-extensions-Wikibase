<?php

namespace Wikibase\Repo\Tests;

/**
 * Should be used in conjunction with MediaWikiIntegrationTestCase
 *
 * @license GPL-2.0-or-later
 */
trait WikibaseTablesUsed {

	private function markTableUsed( string $table ) {
		$this->tablesUsed[$table] = $table;
	}

	protected function markTablesUsedForEntityEditing() {
		$this->markAnyTermsStorageUsed();
		$this->markPropertyInfoTableUsed();
		$this->markItemsPerSiteTableUsed();
		// Adding the page table means MediaWikiIntegrationTestCase will reset all content related tables
		$this->markTableUsed( 'page' );
	}

	private function markPropertyInfoTableUsed() {
		$this->markTableUsed( 'wb_property_info' );
	}

	private function markItemsPerSiteTableUsed() {
		$this->markTableUsed( 'wb_items_per_site' );
	}

	private function markChangePropTablesUsed() {
		$this->markTableUsed( 'wb_changes' );
		$this->markTableUsed( 'wb_changes_subscription' );
	}

	private function markAnyTermsStorageUsed() {
		$this->markTableUsed( 'wbt_item_terms' );
		$this->markTableUsed( 'wbt_property_terms' );
		$this->markSharedTermsTablesUsed();
	}

	private function markSharedTermsTablesUsed() {
		$this->markTableUsed( 'wbt_type' );
		$this->markTableUsed( 'wbt_text' );
		$this->markTableUsed( 'wbt_text_in_lang' );
		$this->markTableUsed( 'wbt_term_in_lang' );
	}

}
