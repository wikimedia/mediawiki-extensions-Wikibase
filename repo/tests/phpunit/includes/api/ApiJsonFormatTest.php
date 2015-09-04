<?php

namespace Wikibase\Test\Repo\Api;

use ApiBase;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
class ApiJsonFormatTest extends ApiFormatTestCase {

	private function getExpectedJson( $moduleIdentifier ) {
		$json = file_get_contents( __DIR__ . '/../../data/api/' . $moduleIdentifier . '.json' );
		$json = json_decode( $json, true );
		return $this->replaceIdsInArray( $json );
	}

	private function replaceIdsInArray( array $array ) {
		$replacements = array();
		if ( $this->lastPropertyId !== null ) {
			$replacements['$propertyIdUnderTest'] = $this->lastPropertyId->getSerialization();
		}
		if ( $this->lastItemId !== null ) {
			$replacements['$itemIdUnderTest'] = $this->lastItemId->getSerialization();
		}
		if ( $replacements ) {
			foreach ( $array as $key => $val ) {
				if ( is_string( $val ) && isset( $replacements[$val] ) ) {
					$array[$key] = $replacements[$val];
				} elseif ( is_array( $val ) ) {
					$array[$key] = $this->replaceIdsInArray( $val );
				}
			}
		}
		return $array;
	}

	private function removePageInfoAttributes( array $result, $entityId = null ) {
		$attributesToRemove = array( 'lastrevid' );

		foreach ( $attributesToRemove as $attributeToRemove ) {
			unset( $result['entity'][$attributeToRemove] );
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

	public function testSetLabelJsonFormat() {
		$entityRevision = $this->getNewEntityRevision();
		$entityId = $entityRevision->getEntity()->getId()->getSerialization();

		$params = array(
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => 'enGbLabel',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetLabel', 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertEquals( $this->getExpectedJson( 'setlabel' ), $actual );

		$params = array(
			'action' => 'wbsetlabel',
			'id' => $entityId,
			'language' => 'en-gb',
			'value' => '',
		);

		$module = $this->getApiModule( '\Wikibase\Repo\Api\SetLabel', 'wbsetlabel', $params, true );
		$result = $this->executeApiModule( $module );
		$actual = $this->removePageInfoAttributes( $result, $entityId );

		$this->assertEquals( $this->getExpectedJson( 'setlabel-removed' ), $actual );
	}

}
