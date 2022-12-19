<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use Wikibase\Repo\Api\GetEntities;
use Wikibase\Repo\Api\SetLabel;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
class ApiJsonFormatTest extends ApiFormatTestCase {

	private function getExpectedJson( $moduleIdentifier ) {
		$json = file_get_contents( __DIR__ . '/../../data/api/' . $moduleIdentifier . '.json' );
		$json = json_decode( $json, true );
		return $this->replaceIdsInArray( $json );
	}

	private function replaceIdsInArray( array $array ) {
		$replacements = [];
		if ( $this->lastPropertyId !== null ) {
			$replacements['$propertyIdUnderTest'] = $this->lastPropertyId->getSerialization();
		}
		if ( $this->lastItemId !== null ) {
			$replacements['$itemIdUnderTest'] = $this->lastItemId->getSerialization();
		}
		if ( $replacements ) {
			foreach ( $array as $key => $val ) {
				$newKey = null;
				$newVal = null;
				foreach ( $replacements as $before => $after ) {
					if ( is_string( $key ) && strstr( $key, $before ) !== false ) {
						// replace keys...
						$newKey = str_replace( $before, $after, $key );
					}
					if ( is_string( $val ) && strstr( $val, $before ) !== false ) {
						// ...and values
						$newVal = str_replace( $before, $after, $val );
					} elseif ( is_array( $val ) ) {
						// recursively
						$newVal = $this->replaceIdsInArray( $val );
					}
				}
				if ( $newKey !== null ) {
					$array[$newKey] = $newVal === null ? $val : $newVal;
					unset( $array[$key] );
				} elseif ( $newVal !== null ) {
					$array[$key] = $newVal;
				}
			}
		}
		return $array;
	}

	private function removePageInfoAttributes( array $result ) {
		$attributesToRemove = [ 'pageid', 'lastrevid', 'modified', 'title', 'ns' ];

		foreach ( $attributesToRemove as $attributeToRemove ) {
			if ( isset( $result['entity'] ) ) {
				unset( $result['entity'][$attributeToRemove] );
			} elseif ( isset( $result['entities'] ) ) {
				foreach ( $result['entities'] as $entityId => $serialization ) {
					unset( $result['entities'][$entityId][$attributeToRemove] );
				}
			}
		}

		return $result;
	}

	/**
	 * This mimics ApiMain::executeAction with the relevant parts,
	 * including setupExternalResponse where the printer is set.
	 * The module is then executed and results printed.
	 */
	private function executeApiModule( ApiBase $module ) {
		$printer = $module->getMain()->createPrinterByName( 'json' );

		$module->execute();

		$printer->initPrinter();
		$printer->disable();

		$printer->execute();

		return json_decode( $printer->getBuffer(), true );
	}

	/**
	 * @covers \Wikibase\Repo\Api\GetEntities
	 */
	public function testGetEntitiesJsonFormat() {
		$entityRevision = $this->getNewEntityRevision( true );
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbgetentities',
			'ids' => $entityId,
		];

		$module = $this->getApiModule( GetEntities::class, 'wbgetentities', $params );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );
		$actual = $this->replaceHashesWithPlaceholder( $actual );

		$this->assertEquals( $this->getExpectedJson( 'getentities' ), $actual );
	}

	/**
	 * @covers \Wikibase\Repo\Api\SetLabel
	 */
	public function testSetLabelJsonFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = [
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbLabel',
		];

		$module = $this->getApiModule( SetLabel::class, 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );

		$this->assertEquals( $this->getExpectedJson( 'setlabel' ), $actual );

		$params = [
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => '',
		];

		$module = $this->getApiModule( SetLabel::class, 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result );

		$this->assertEquals( $this->getExpectedJson( 'setlabel-removed' ), $actual );
	}

	private function replaceHashesWithPlaceholder( array $json ) {
		array_walk_recursive(
			$json,
			function( &$value, $key ) {
				if ( $key === 'hash' ) {
					$value = 'XXX';
				}
			}
		);
		return $json;
	}

}
