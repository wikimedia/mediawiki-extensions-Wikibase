<?php
declare( strict_types=1 );
namespace Wikibase\Repo\FederatedProperties;

use Wikibase\DataAccess\ApiEntitySource;
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
			$sources[] = new ApiEntitySource(
				'fedprops',
				[ Property::ENTITY_TYPE ],
				'http://www.wikidata.org/entity/',
				'fpwd',
				'fpwd',
				'wikidata'
			);
			return new EntitySourceDefinitions( $sources, $this->subTypeMapper );
		}
		return $existingDefinitions;
	}

}
