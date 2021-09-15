# RDF binding

Wikibase allows exporting data in RDF format. See details of the implementation in [RDF Dump Format](https://www.mediawiki.org/wiki/Wikibase/Indexing/RDF_Dump_Format)

Changes to the RDF mapping are however subject to the Stable Interface Policy, see [Wikidata:Stable Interface Policy](https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy).

# Exporting RDF

Export RDF data from a Wikibase Repository is done with the maintenance script `repo/maintenance/dumpRdf.php`

By executing the following the Repository contents will be output to the console in RDF format.

```sh
$ php extensions/Wikibase/repo/maintenance/dumpRdf.php

Dumping entities of type item, property
Dumping shard 0/1
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .
@prefix wikibase: <http://wikiba.se/ontology#> .
...
```

##### Parameters

* `--format` Set the dump format, such as "nt" or "ttl". Defaults to "ttl"
* `--flavor` Set the flavor to produce. Can be either "full-dump" or "truthy-dump". Defaults to "full-dump".
    - `"full-dump"` contains all statements regardless of their rank.
    - `"truthy-dump"` does not contain statements with a deprecated rank. If there are statements with normal and preferred rank, only the statements with preferred rank will be exported.
* `--redirect-only` Whether to only export information about redirects.
* `--part-id` Unique identifier for this part of multi-part dump, to be used for marking bnodes.

Change log
----------

The following are the RDF format versions, see FORMAT_VERSION in `RdfVocabulary.php`.

-   0.0.1 – Initial implementation
-   0.0.2 – Changed WKT coordinate order (see T130049)
-   0.0.3 – Added page props option to wdata: (see T129046)
-   0.0.4 – Added unit conversion &pan normalization support (see T117031)
-   0.0.5 – Added quantities without bounds (see T115269)
-   0.1.0 – Changed sitelink encoding (see T131960)
-   1.0.0 – Moved ontology URI to [<http://wikiba.se/ontology>\# Ontology] (removed beta) (see T112127)
