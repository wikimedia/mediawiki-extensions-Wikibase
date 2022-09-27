# Hooks JS

This file describes JavaScript [hooks] defined by the Wikibase extensions.

[TOC]

#### wikibase.entityPage.entityView.rendered
  * Called after the Wikibase UI is initialized.

[View source][entityViewInit.js]

#### wikibase.entityPage.entityLoaded
  * Called as soon as the JSON representing the entity stored on the current entity page is loaded.
  * Listener callbacks should expect the entity as a native JavaScript object (the [parsed JSON serialization]) passed as the first argument.

[View source][entityLoaded.js]

#### wikibase.statement.saved
  * Called after a statement has been saved.
  * Passed arguments:
    * entity ID (string)
    * statement ID (string)
    * old statement ([Javascript Wikibase DataModel][] [Statement][] or null in case of a new statement)
    * new statement ([Statement][])

[View source][StatementsChanger.js]

#### wikibase.statement.removed
  * Called after a statement has been removed. Entity ID and statement ID are passed as arguments.

[View source][StatementsChanger.js]

#### wikibase.entityselector.search
  * Called when entity selector fetches suggestions.
  * An object containing the following elements is passed as first argument :element, term and options. As second argument a function is passed allowing to add promises that return additional suggestion items. Those items will replace existing items with the same ID and will be placed on top of the list. If an item has a property `rating` then this property will be used for sorting the list by it descending (higher `rating` on top). The range of the `rating` should be between 0-1.

[View source][jquery.wikibase.entityselector.js]

#### wikibase.statement.startEditing
  * Called when entering the edit mode for an existing statement. Gets the statement's guid passed as parameter.

[View source][jquery.wikibase.statementview.js]

#### wikibase.statement.stopEditing
  * Called when leaving the edit mode for an existing statement. Gets the statement's guid passed as parameter.

[View source][jquery.wikibase.statementview.js]

[entityViewInit.js]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/master/repo/resources/wikibase.ui.entityViewInit.js
[entityLoaded.js]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/master/repo/resources/wikibase.entityPage.entityLoaded.js
[hooks]: https://doc.wikimedia.org/mediawiki-core/master/js/#!/api/mw.hook
[StatementsChanger.js]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/refs/heads/master/view/resources/wikibase/entityChangers/StatementsChanger.js
[jquery.wikibase.entityselector.js]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/refs/heads/master/view/resources/jquery/wikibase/jquery.wikibase.entityselector.js
[jquery.wikibase.statementview.js]: https://gerrit.wikimedia.org/g/mediawiki/extensions/Wikibase/+/refs/heads/master/view/resources/jquery/wikibase/jquery.wikibase.statementview.js
[Javascript Wikibase Datamodel]: https://phabricator.wikimedia.org/source/wikibase-data-model/
[Statement]: https://phabricator.wikimedia.org/source/wikibase-data-model/browse/master/src/Statement.js
[parsed JSON serialization]: @ref docs_topics_json
