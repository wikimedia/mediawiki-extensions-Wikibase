<?php
declare( strict_types=1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @license GPL-2.0-or-later
 */
class DefaultFederatedPropertiesEntitySourceAdder {

	/**
	 * @var bool
	 */
	private $federatedPropertiesEnabled;
	/**
	 * @var string
	 */
	private $sourceScriptUrl;

	/**
	 * @var SubEntityTypesMapper
	 */
	private $subTypeMapper;

	/**
	 * @param bool $federatedPropertiesEnabled
	 * @param string $sourceScriptUrl
	 */
	public function __construct( bool $federatedPropertiesEnabled, string $sourceScriptUrl, SubEntityTypesMapper $subTypeMapper ) {
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->sourceScriptUrl = $sourceScriptUrl;
		$this->subTypeMapper = $subTypeMapper;
	}

	public function addDefaultIfRequired( EntitySourceDefinitions $existingDefinitions ): EntitySourceDefinitions {
		if (
			$this->federatedPropertiesEnabled &&
			$this->sourceScriptUrl === 'https://www.wikidata.org/w/' &&
			count( $existingDefinitions->getSources() ) === 1
			) {
			$sources = $existingDefinitions->getSources();
			$sources[] = new EntitySource(
				'fedprops',
				false,
				[ Property::ENTITY_TYPE => [
					'namespaceId' => 0,
					'slot' => 'main'
				] ], # FIXME: T288524
				'http://www.wikidata.org/entity/',
				'fpwd',
				'fpwd',
				'wikidata',
				EntitySource::TYPE_API
			);
			return new EntitySourceDefinitions( $sources, $this->subTypeMapper );
		}
		return $existingDefinitions;
	}

}
