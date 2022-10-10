<?php
/**
 * @file
 * A collection of namespace doc blocks to be included in the generated Doxygen.
 * This code is not actually loaded at all by Wikibase.
 *
 * @internal
 * phpcs:ignoreFile
 */

/**
 * @brief Wikibase extension Code relating to the <a href="https://github.com/DataValues">DataValues composer packages</a>
 */
namespace DataValues;

/**
 * @brief Root namespace for the Wikibase extension
 */
namespace Wikibase;

/**
 * @brief Code that only relates to the development in Wikibase.git
 */
namespace Wikibase\Build;

/**
 * @brief Root namespace for Client extension code
 */
namespace Wikibase\Client;

/**
 * @brief Client integration with <a href="https://www.mediawiki.org/wiki/API:Extensions">MediaWiki's Action API</a>
 */
namespace Wikibase\Client\Api;

/**
 * @brief Handling for [EntityChanges](@ref EntityChange) from a Repo
 */
namespace Wikibase\Client\Changes;

/**
 * @brief Root namespace for the Client component DataBridge
 */
namespace Wikibase\Client\DataBridge;

/**
 * @brief Accessing Repo data from a Client
 */
namespace Wikibase\Client\DataAccess;

/**
 * @brief Client data access implementations of <a href="https://www.mediawiki.org/wiki/Manual:Parser_functions">MediaWiki's Parser functions</a>
 */
namespace Wikibase\Client\DataAccess\ParserFunctions;

/**
 * @brief Client integration with the <a href="https://www.mediawiki.org/wiki/Extension:Scribunto">Scribunto extension</a>. (LUA data access)
 */
namespace Wikibase\Client\DataAccess\Scribunto;

/**
 * @brief Client handling of <a href="https://www.mediawiki.org/wiki/Manual:Hooks">MediaWiki's Hooks</a>
 */
namespace Wikibase\Client\Hooks;

/**
 * @brief Client integration with the <a href="https://www.mediawiki.org/wiki/Extension:Echo">Echo extension</a>
 *
 * This Notifications namespace has a totally different definition to Wikibase\Repo\Notifications
 */
namespace Wikibase\Client\Notifications;

/**
 * @brief ParserOutput integration for Client pages that use Repo entities
 */
namespace Wikibase\Client\ParserOutput;

/**
 * @brief Display of Repo changes on a Client <a href="https://www.mediawiki.org/wiki/Help:Recent_changes">RecentChanges page</a>
 */
namespace Wikibase\Client\RecentChanges;

/**
 * @brief Client integration with <a href="https://www.mediawiki.org/wiki/Manual:Special_pages">MediaWiki's Special pages</a>
 */
namespace Wikibase\Client\Specials;

/**
 * @brief Root namespace for Client extension test code
 */
namespace Wikibase\Client\Tests;

/**
 * @brief Updates to a Repo after events on a Client
 */
namespace Wikibase\Client\UpdateRepo;
/**
 * @brief Tracking the usage of Repo entities on a Client (see usagetracking.md)
 */
namespace Wikibase\Client\Usage;

/**
 * @brief Root namespace for DataAccess component code
 */
namespace Wikibase\DataAccess;

/**
 * @brief Root namespace for DataAccess component test code
 */
namespace Wikibase\DataAccess\Tests;

/**
 * @brief Code relating to dumping Repo entities
 */
namespace Wikibase\Repo\Dumpers;

/**
 * @brief Root namespace for Lib extension code
 */
namespace Wikibase\Lib;

/**
 * @brief Various random 'Interactors' that do 'things' (ill defined)
 *
 * @deprecated Code in this namespace should move to more relevant namespaces
 */
namespace Wikibase\Lib\Interactors;

/**
 * @brief Lib implementations of <a href="https://www.mediawiki.org/wiki/Manual:Parser_functions">MediaWiki's Parser functions</a>
 */
namespace Wikibase\Lib\ParserFunctions;

/**
 * @brief Handling and reporting exceptions
 */
namespace Wikibase\Lib\Reporting;

/**
 * @brief Management of a the <a href="https://doc.wikimedia.org/mediawiki-core/master/php/interfaceSiteStore.html">MediaWiki SiteStore</a>
 */
namespace Wikibase\Lib\Sites;

/**
 * @brief Root namespace for Lib extension test code
 */
namespace Wikibase\Lib\Tests;

/**
 * @brief Everything RDF related
 */
namespace Wikibase\Rdf;

/**
 * @brief Root namespace for Repo extension code
 */
namespace Wikibase\Repo;

/**
 * @brief Repo implementations of <a href="https://www.mediawiki.org/wiki/API:Extensions">MediaWiki's Action API</a>
 */
namespace Wikibase\Repo\Api;

/**
 * @brief Repo integration with <a href="https://www.mediawiki.org/wiki/Manual:ContentHandler">MediaWiki's Content mechanism</a>
 */
namespace Wikibase\Repo\Content;

/**
 * @brief Interfaces and Implementations for editing Wikibase entities
 */
namespace Wikibase\Repo\EditEntity;

/**
 * @brief Repo handling of <a href="https://www.mediawiki.org/wiki/Manual:Hooks">MediaWiki's Hooks</a>
 */
namespace Wikibase\Repo\Hooks;

/**
 * @brief Various random 'Interactors' that do 'things' (ill defined)
 *
 * @deprecated Code in this namespace should move to more relevant namespaces
 */
namespace Wikibase\Repo\Interactors;

/**
 * @brief Repo integration with <a href="https://www.mediawiki.org/wiki/Manual:Maintenance_scripts">MediaWiki's maintenance scripts</a>
 */
namespace Wikibase\Repo\Maintenance;

/**
 * @brief '%Notifications' of Repo changes to Client sites
 *
 * Unlike Wikibase\Client\Notifications this namespace has nothing to do with the <a href="https://www.mediawiki.org/wiki/Extension:Echo">Echo extension</a>
 */
namespace Wikibase\Repo\Notifications;

/**
 * @brief ParserOutput integration for pages that are entities
 */
namespace Wikibase\Repo\ParserOutput;

/**
 * @brief Entity search on a Repo
 */
namespace Wikibase\Repo\Search;

/**
 * @brief Repo integration with <a href="https://www.mediawiki.org/wiki/Manual:Special_pages">MediaWiki's Special pages</a>
 */
namespace Wikibase\Repo\Specials;

/**
 * @brief Root namespace for Repo extension test code
 */
namespace Wikibase\Repo\Tests;

/**
 * @brief Root namespace for View extension code
 */
namespace Wikibase\View;

/**
 * @brief Root namespace for View component Termbox
 */
namespace Wikibase\View\Termbox;

/**
 * @brief Root namespace for View extension test code
 */
namespace Wikibase\View\Tests;

/**
 * @brief Root namespace for test code that is not part of one of the extensions
 *
 * @deprecated Tests in this namespace should live in the Tests namespace for the respective extension
 */
namespace Wikibase\Tests;
