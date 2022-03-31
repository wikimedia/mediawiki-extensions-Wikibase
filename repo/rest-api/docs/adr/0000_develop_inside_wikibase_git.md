# 0) Develop the Wikibase REST API inside Wikibase.git {#rest_adr_0000}

Date: 2022-02-23

## Status

accepted

## Context

Decisions on how the development, testing and delivery infrastructure for Wikibase REST API are going to be set up, seem to influence and impact each of those infrastructure elements near- and mid-term. While the Wikibase Product Platform Team claims a right to adjust, adapt, and change any decisions over time when it seems valuable, we’d also like to make an initial call on how to set those pieces of environment and infrastructure that will be the most sensible based on our current knowledge and understanding of constraints and opportunities.

## Options
We considered five different options where and how to develop an MVP of the Wikibase REST API:
- Directly within Wikibase.git
- As a package within the Wikibase.git monorepo
- As a separate MediaWiki extension in its own repository
- As a dependency (library) of mediawiki/wikibase
- Without binding to MediaWiki at all

### Option 1: REST API within Wikibase.git

<table>
    <tr>
        <th>Option Description</th>
        <td>Development happens in Wikibase.git master branch, and the REST API lives within the Wikibase Repo extension</td>
    </tr>
    <tr>
        <th>VCS Repository</th>
        <td>Wikibase.git</td>
    </tr>
    <tr>
        <th>CI System</th>
        <td>Comes “for free” via existing Wikibase CI and CI on backports to REL branches</td>
    </tr>
    <tr>
        <th>Release Version Compatibility</th>
        <td>Develop on master, ensuring the API is compatible with needed major versions of MediaWiki</td>
    </tr>
    <tr>
        <th>Test System</th>
        <td>Any, but Beta Wikidata might come for free</td>
    </tr>
    <tr>
        <th>Remarks</th>
        <td>
            <ul>
                <li>Keeping the API in Wikibase.git makes it very clear which API version is compatible with which WB version</li>
                <li>Likely the most backporting (in commit volume) but similarly complex as if kept in a separate extension</li>
                <li>Many things come “for free” (boilerplate, CI, other infrastructure) but at the cost of flexibility</li>
                <li>Leaves some room for accidental mistakes regarding the "clean architecture" approach.</li>
                <li>Slow CI</li>
            </ul>
        </td>
    </tr>
</table>

### Option 2: REST API as a package within the Wikibase.git monorepo

<table>
    <tr>
        <th>Option Description</th>
        <td>Development happens in Wikibase.git master branch, but as a separate “Wikibase package” independent from “main” Wikibase</td>
    </tr>
    <tr>
        <th>VCS Repository</th>
        <td>Wikibase.git</td>
    </tr>
    <tr>
        <th>CI System</th>
        <td>Comes for free via existing Wikibase CI and CI on backports to REL branches</td>
    </tr>
    <tr>
        <th>Release Version Compatibility</th>
        <td>Develop on master, backport when needed</td>
    </tr>
    <tr>
        <th>Test System</th>
        <td>Any, but Beta Wikidata might come for free</td>
    </tr>
    <tr>
        <th>Remarks</th>
        <td>
        Very similar to 1, but:
        <ul>
            <li>Enforces some additional separation from Wikibase legacy monolith, which could serve the "clean architecture" approach.</li>
            <li>The API code can likely not live within a package entirely, because certain things (API endpoint implementations, concrete service implementations, places where dependencies from Wikibase internals are injected) depend on Wikibase and/or MediaWiki</li>
            <li>Might result in business logic living within the package, and “details” such as concrete implementations and “infrastructure” outside of it, which may be confusing</li>
        </ul>
        </td>
    </tr>
</table>


### Option 3: REST API in separate MediaWiki extension

<table>
    <tr>
        <th>Option Description</th>
        <td>Development happens in a separate MediaWiki extension</td>
    </tr>
    <tr>
        <th>VCS Repository</th>
        <td>Either inside Wikibase.git or in a separate repository. Might benefit from Gerrit “Depends-On” features with Wikibase</td>
    </tr>
    <tr>
        <th>CI System</th>
        <td>(?) Run with Wikibase master on our master and use REL branch for compatibility test</td>
    </tr>
    <tr>
        <th>Release Version Compatibility</th>
        <td>
        <ul>
            <li>Backport only required changes to Wikibase.git internals to REL branches</li>
            <li>(?) Develop against Wikibase.git master by default and have a REL branch for the API extension (might help with CI and reduces confusion)</li>
        </ul>
        </td>
    </tr>
    <tr>
        <th>Test System</th>
        <td>Any</td>
    </tr>
    <tr>
        <th>Remarks</th>
        <td>
        <ul>
            <li>Not very different from developing inside the Wikibase (repo) extension, but possibly nicer separation</li>
            <li>A little more freedom</li>
            <li>Possibly faster CI</li>
            <li>More boilerplate</li>
            <li>Free feature toggle</li>
            <li>Deploying on Wikidata would require an additional WMF security review</li>
        </ul>
        </td>
    </tr>
</table>

### Option 4: REST API as a dependency (library) of MediaWiki/Wikibase


<table>
    <tr>
        <th>Option Description</th>
        <td>Package the API as a somewhat isolated library that would be loaded as a composer library</td>
    </tr>
    <tr>
        <th>VCS Repository</th>
        <td>Anywhere</td>
    </tr>
    <tr>
        <th>CI System</th>
        <td>Any</td>
    </tr>
    <tr>
        <th>Release Version Compatibility</th>
        <td>Same as Option 2: Develop against master, backport when needed</td>
    </tr>
    <tr>
        <th>Test System</th>
        <td>Any</td>
    </tr>
    <tr>
        <th>Remarks</th>
        <td>
        <ul>
            <li>Unclear, if an API can sensibly be built entirely as a library: eventually, some application code will be needed.</li>
            <li>Thus it is similar to Option 2, but with some additional constraints.</li>
        </ul>
        </td>
    </tr>
</table>

### Option 5: REST API without binding to MediaWiki at all (separate process)

<table>
    <tr>
        <th>Option Description</th>
        <td>Create the REST API as an entirely separate service that doesn’t live within MediaWiki/Wikibase.</td>
    </tr>
    <tr>
        <th>VCS Repository</th>
        <td>Anywhere</td>
    </tr>
    <tr>
        <th>CI System</th>
        <td>Any</td>
    </tr>
    <tr>
        <th>Release Version Compatibility</th>
        <td>Backport required changes to Wikibase or have a compatibility layer for the older versions within the API</td>
    </tr>
    <tr>
        <th>Test System</th>
        <td>Any</td>
    </tr>
    <tr>
        <th>Remarks</th>
        <td>
        Lots of freedom, but accessing data from Wikibase becomes tricky. Direct SQL access and/or using the existing action API both don’t sound appealing. Instead it would probably require an entirely new API/interface/protocol for accessing wikibase data, and building a new API on top of a new API doesn’t sound great either.
        </td>
    </tr>
</table>

## Decision

The team has decided to go with option 1 and develop the REST API inside the core Wikibase.git repository, using the subdirectory /repo/rest-api/.

Reasons:
  * The team came to a consensus, that the Wikibase REST API should be an integral part of the Wikibase Repo extension.
  * Different options of implementing this approach (option 1 to 4 above) seem to have only few differences between each other, mostly regarding restrictions in code organisation.
  * While we aim to have a clear architectural boundary between the REST API code and the Wikibase internals, delivering the code as a fully independent package/library (options 2 and 4) to Wikibase seems impractical. We still want to build concrete data access services on top of existing Wikibase and MediaWiki implementation, and benefit from MediaWiki infrastructure for service wiring, registering the REST API endpoints and extending API handler classes. In order to also keep the benefits of having the related code, tests, and configuration closely together, we will instead keep everything within a directory in Wikibase/repo/ and enforce the dependency rules by other means, such as architecture checks in the linter.
  * Previous experience with the "Clean Architecture" approach by the FUN team suggests not to be too strict with repository separation and rather take the pragmatic approach of staying within Wikibase.git.
  * For some of the options (e.g. option 5) it is not currently clear, how exactly they would end up looking in practice.

This decision for an MVP of the REST API does not mean, that alternative options are ruled out for all future. Changes in code organisation, git repos or the testing system will always remain possible.
