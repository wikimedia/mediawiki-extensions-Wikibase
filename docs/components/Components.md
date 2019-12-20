# Components {#components}

%Wikibase is made up of multiple components, some of which have sub components.

The top level Mediawiki extensions are:

* %Wikibase Repository (in the `repo` directory)
* %Wikibase Client (in the `client` directory)
* %Wikibase Lib (in the `lib` directory)
* @subpage components_view (in the `view` directory)

The **repo** is the extension for the repository. It allows the creation and maintenance of structured data.
This is being used on [wikidata.org](https://www.wikidata.org).

The **client** is the extension for the client.
It allows several MediaWiki instances to use data provided by a Wikidata instance.
Usually, you would not use them in a single wiki.
This is being used on the Wikipedias.

The **lib** bundles common code that is used by both the client and the repo.

**View** is the component responsible for the HTML-based frontend for the repo.
