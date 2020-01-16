# Libraries

Wikibase makes use of multiple PHP libraries which are installed via [composer](https://getcomposer.org/).

A summary is provided below, you can always find the list of libraries in the [composer.json] file.

### Maintained by Wikibase Developers

These libraries are developed outside the Wikibase.git repo so that they can easily be consumed by other projects.

* data-values - All libs have their source in the [DataValues Github org](https://github.com/DataValues) (expect one)
  * [data-values](https://packagist.org/packages/data-values/data-values)
  * [common](https://packagist.org/packages/data-values/common)
  * [geo](https://packagist.org/packages/data-values/geo)
  * [interfaces](https://packagist.org/packages/data-values/interfaces)
  * [time](https://packagist.org/packages/data-values/time) (source is in [wmde Github org](https://github.com/wmde))
  * [serialization](https://packagist.org/packages/data-values/serialization)
* wikibase - All libs have their source in the wmde Github org
  * [data-model](https://github.com/wmde/WikibaseDataModel)
  * [data-serialization](https://github.com/wmde/WikibaseDataModelSerialization)
  * [internal-serialization](https://github.com/wmde/WikibaseInternalSerialization)
  * [data-model-services](https://github.com/wmde/WikibaseDataModelServices)
  * [term-store](https://github.com/wmde/wikibase-term-store)
* [diff/diff](https://github.com/wmde/Diff) - WMDE Github org
* [wikimedia/purtle](https://github.com/wikimedia/purtle) - Wikimedia Github org

### External

* psr
  * [log](https://packagist.org/packages/psr/log) - ([PSR-3](https://www.php-fig.org/psr/psr-3/))
  * [simple-cache](https://packagist.org/packages/psr/simple-cache) - ([PSR-16](https://www.php-fig.org/psr/psr-16/))
* onoi/message-reporter

[composer.json]: @ref composer.json
