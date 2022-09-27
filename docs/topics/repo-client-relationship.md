# Repo Client Relationship

A Wikibase repo can have 0 or more attached clients. It is common for a repo to also itself be a client.

For Wikidata, www.wikidata.org is a repository and a client of itself. Most other Wikimedia sites are then also clients of www.wikidata.org.

In order for full repo client functionality, if using multiple sites, they must have direct database access with one another.

The Repo client relationship allows:
 - Repos to link to clients in sitelinks
 - Clients to use data from a repo

Various mechanisms come into play here, and you may find the following topics useful:
 - @ref docs_topics_change-propagation
 - @ref docs_topics_usagetracking

## Setups

### A single wiki, repo & client together

The most basic setup is where a single site is both a repo and a client.
By default if you enable both the repo and client extensions on a single site they will automatically connect together for data use.

If you want to use the sitelink functionality you will need to add a MediaWiki sites table entry.

You can do this using the `addSite.php` maintenance script in MediaWiki core, for example:

```
php maintenance/addSite.php mywiki default --interwiki-id mywiki --pagepath http://localhost/w/index.php?title=\$1 --filepath http://localhost/w/\$1
```

This will add the site to the default site group, which is enabled by default.

### A separate repo and client wiki

If you have two or more sites to setup, they must have direct database access.
One of them must be running repo and one or more must be running client.
The repo can also be a client.

**Setup shared entity source**

Firstly you need to define a shared entity source definition in both the repo and client configuration.
You can find an example of a basic entity source in the [entitysources topic].

You then need to populate the sites table for all sites with all sites.
For a two site setup that would require running the `addSite.php` maintenance script twice per site (once for each site being added).

```
php maintenance/addSite.php myrepo default --pagepath 'http://myrepo.web.mw.localhost:8080/index.php?title=$1' --filepath 'http://myrepo.web.mw.localhost:8080/$1'
php maintenance/addSite.php myclient default --pagepath 'http://myclient.web.mw.localhost:8080/index.php?title=$1' --filepath 'http://myclient.web.mw.localhost:8080/$1'
```

You should then be able to take advantage of the repo client relationship.

### Sitematrix sites

All Wikimedia sites have the [SiteMatrix] extension installed which contains details of all sites.

In situations like this the `populateSitesTable.php` maintenance script can be used to populate the sites tables.

entitySources will still need to be configured as above.

[entitysources]: @ref docs_topics_entitysources
[SiteMatrix]: https://www.mediawiki.org/wiki/Extension:SiteMatrix
