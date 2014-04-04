These is the readme file for the WikibaseLib extension.

Extension page on mediawiki.org: https://www.mediawiki.org/wiki/Extension:WikibaseLib
Latest version of the readme file: https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/Wikibase.git;a=blob;f=lib/README

## About

WikibaseLib holds common code for the Wikibase and WikibaseClient extensions.

### Feature overview

* Objects that handle entities and items
* Objects to represent SiteLinks
* Change propagation system:
** Objects to represent changes
** ChangeNotifier interface
** Change dispatcher maintenance script to submit jobs to clients
* Store interfaces for SiteLink lookups and SiteLink caching
* Maintenance scripts to rebuild or delete all data
