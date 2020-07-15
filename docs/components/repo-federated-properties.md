# Federated Properties

Federated Properties is a feature that allows a newly created Wikibase instance to use the existing Properties of another Wikibase. This enables new users evaluating Wikibase to get started without having to spend a lot of time defining basic Properties first.

## Installation

The setting is off by default. To enable Federated Properties from [Wikidata], set <code>$wgWBRepoSettings['federatedPropertiesEnabled'] = true;</code> in your wiki's <code>LocalSettings.php</code>. To configure a different source wiki, the [federatedPropertiesSourceScriptUrl setting] must be set accordingly to the source wiki's script path url, e.g. <code>$wgWBRepoSettings['federatedPropertiesSourceScriptUrl'] = 'https://wikidata.beta.wmflabs.org/w/';</code>.

## Configuring EntitySources

Using federated properties by only enabling the feature will default the `federatedPropertiesSourceScriptUrl` to https://www.wikidata.org/w/. In this case no additional configuration of ```entitysources``` is required.

Using the feature with any other source wiki will however require `entitysources` to be manually configured in order to get the correct RDF representation of entities.

The following example is a configuration of `entitysources` similar to what is automatically generated. However here we are using **wikidata.beta.wmflabs.org** as the source wiki.

Adding this to the `LocalSettings.php` of **wikidata-federated-properties.wmflabs.org** would result in the RDF representation to be correct.

```
$wgWBRepoSettings['entitySources'] = [
	'local' => [
		'entityNamespaces' => [ 'item' => 120 ],
		'repoDatabase' => false,
		'baseUri' => 'http://wikidata-federated-properties.wmflabs.org/entity/',
		'interwikiPrefix' => '',
		'rdfNodeNamespacePrefix' => 'wd',
		'rdfPredicateNamespacePrefix' => 'wdt',
	],
	'fedprops' => [
		'entityNamespaces' => [ 'property' => 120 ],
		'repoDatabase' => false,
		'baseUri' => 'http://wikidata.beta.wmflabs.org/entity/',
		'interwikiPrefix' => 'wd',
		'rdfNodeNamespacePrefix' => 'fpwd',
		'rdfPredicateNamespacePrefix' => 'fpwd',
	],
];
```

The two configurations contain separate sources for the local `item` and federated `property`. The `entityNamespaces` for these entities depends on the configuration of that particular wiki. For more information on the different `entityNamespaces` in Wikidata see https://www.wikidata.org/wiki/Help:Namespaces.

The `interwikiPrefix` is a configuration to support links between MediaWiki instances. This also depend on the configuration on each instance and should ideally point to a wiki defined in the `interwiki` table  For more information on interwiki links see https://www.mediawiki.org/wiki/Manual:Interwiki.
## Privacy notice

Once you enable Federated Properties in your Wikibase installation, all requests to the federation source Wiki will include an anonymized unique identifier as the useragent. This will be used only to detect abnormal traffic to the source Wiki for the purposes of preventing abuse.

## Limitations

For now the feature is not intended for production use. It is only meant to facilitate the evaluation of Wikibase as a software for third party use cases.

Federated Properties must only be enabled for a fresh Wikibase installation without any existing local Properties. Local Properties and Federated Properties cannot coexist on a Wikibase at the same time. The setting should be considered permanent after entering any data into the wiki (see [Known Issues](@ref known-issues)).

## Implementation

The following sections describe the implementation details of the Federated Properties feature. It is intended for developers working on the code, and those who want to know what is going on under the hood.

### Requesting Data from the Source Wiki

A Wikibase with Federated Properties enabled fetches data about those Properties using the source wiki's HTTP API. The two endpoints that are currently used are <code>wbsearchentities</code> for searching, and <code>wbgetentities</code> for fetching the data needed to display statements on Item pages and for making edits.

For simplicity's sake the initial API based implementations of data access services such as <code>PropertyDataTypeLookup</code> and <code>PrefetchingTermLookup</code> directly requested the data they need from the API. While effective, this naive approach generates a lot of traffic on the source wiki and is not very performant. Ideally, we want to minimize the number of requests.

As a first measure, an <code>ApiEntityLookup</code> service that wraps calls to <code>wbgetentities</code> was introduced that optimistically requests all data that could possibly be needed (data type, labels, descriptions) to render statements using the Federated Property. The service internally caches the API's response for each Property so that for the duration of an incoming request to the target wiki no data would need to be fetched more than once for the same Property. The service can also request data for multiple Properties at once, so that all data for all Properties that are used in statements of an item page could be fetched in a single request if it is done before any individual requests for Property data happen.

Unfortunately, of the two data access services implemented for Federated Properties, the <code>PropertyDataTypeLookup</code> which looks up data types one at a time is called first so that the batching functionality of the <code>ApiEntityLookup</code> doesn't come into effect. Changing the <code>PropertyDataTypeLookup</code> interface to allow batching data type lookups might work, but a more generic data prefetching mechanism for data of entities that are referenced on a given page seems like the cleaner approach.

In the past, <code>EntityInfoBuilder</code> was used to load data about entities referenced (e.g. properties in statements, or statement values) on entity pages. Upon closer inspection <code>EntityInfo</code> appears to be largely unused, and either needs to be replaced or overhauled in order to be useful for prefetching Federated Properties. See [T253125#6163636].

### Handling IDs of Federated Properties

For the MVP, version IDs of Federated Properties carry no information about their source wiki. The decision is documented in the [ADR about handling Federated Property IDs].

### Known Issues {#known-issues}

In the current implementation, Federated Properties cannot be used in combination with local properties. This means enabling the feature on a Wikibase instance that already contains local properties is not supported. As there is currently no distinction between the two different kinds of properties, using local properties when federation is enabled will either cause them to display as a deleted, or as the property with the same entity ID on the federation source Wiki.

For the same reason, enabling Federated Properties and then turning off the feature is not supported once any statements have been added. Doing so will result in those properties displaying as deleted properties.

[Wikidata]: https://www.wikidata.org/wiki/Wikidata:Main_Page
[federatedPropertiesSourceScriptUrl setting]: @ref repo_federatedPropertiesSourceScriptUrl
[ADR about handling Federated Property IDs]: @ref adr_0010
[T253125#6163636]: https://phabricator.wikimedia.org/T253125#6163636
