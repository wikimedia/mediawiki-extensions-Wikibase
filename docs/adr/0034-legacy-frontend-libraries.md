# 34) Merge legacy frontend libraries, formerly git submodules, to Wikibase repository {#adr_0034}

Date: 2026-03-02

## Status

accepted

## Decision

Legacy Wikibase frontend components `data-values/value-view`, `wikibase-serialization`, `datavalues-javascript`, `wikibase-data-model` and `wikibase/javascript-api` will be merged to Wikibase repositories. Changes made to those components will continue to be published to their respective dedicated repositories. Existing Wikimedia Phabricator Diffusion mirrors of their repositories will be disabled.

## Context

A number of components of the Wikibase legacy (jquery UI based) frontend have historically been developed as standalone packages, i.e. to not have a specific Wikibase or Mediawiki dependencies.

Since 2017 they have been integrated into Wikibase codebase as git submodules. ([T174922][phab-frontend-libs-as-submodules])

Some of those libraries have been developed on Github. As Wikimedia Foundation does not deploy code hosted on third-party infrastructure, in order to allow deployments of Wikibase code including submodules, the Github-hosted components are integrated through WMF-hosted Phabricator mirrors. ([I7b1e3cbafc4a][gerrit-github-submodules-through-phab-mirrors])

In 2025 Wikimedia Phabricator started experiencing availability issues related to crawling. Availability of Phabricator-hosted submodules has been also affected, commonly leading to failures when cloning Wikibase repository.  ([T409519][phab-cloning-timeouts])

There are six components integrated into Wikibase repository as Git submodules. Five of them are related to the Wikibase legacy frontend: `data-values/value-view`, `wikibase-serialization`, `datavalues-javascript`, `wikibase-data-model` and `wikibase/javascript-api`. Of those `wikibase-serialization`, `datavalues-javascript` and `wikibase-data-model` are developed on Github and integrated into Wikibase repository through Phabricator mirrors. Other components are developed on Wikimedia Gerrit and integrated from there.

The remaining submodule component, `wikibase-termbox`, is not related to the legacy frontend, and code is integrated from a Gerrit repository, so it is not affected by Phabricator submodule availability issues. This submodule is not in scope of this Decision and its considerations.

Additionally, Wikibase Lexeme codebase integrates the `new-lexeme-special-page` component as a git submodule from Wikimedia Phabricator (in this case it is also a mirror of a Github repository in which the actual development takes place). That codebase is also affected by Phabricator scraping issues. While Wikibase Lexeme is not in scope of this decision, approach taken might or not be applicable for that other codebase as well.

## Options considered

### Option 1: (SELECTED) Merge the legacy frontend components to Wikibase repository (move away from submodules)

#### Consequences

* Selected because removing legacy frontend components once those become not used seems easier to do when they are close to the primary, if not only, code base using those components.
* Selected because cloning Wikibase repository might become slightly faster due to fewer separate cloning operations involved -- However the size of files in the repository and their history and metadata will increase
* Selected because, while moving those components to Wikibase repository might seem contradicting efforts to organize that codebase with clearly defined boundaries and controlled dependencies, those legacy frontend components are eventually going to be removed as soon as Wikibase UI completes the transition to modern frontend technology. The structure and architecture negative impact could be therefore less problematic as it is not meant to be permanent
* Selected because the history of changes of individual legacy frontend components can be preserved
* Components `data-values/value-view` and `wikibase/javascript-api` are also merged from WMF Gerrit Wikibase repository, despite not being affected by Phabricator availability issues, to reduce the number of systems in play
* Selected because it brings the structure of Wikibase frontend closer to the structure of Wikibase backend logic, as this change in a way mirrors merging in the backend PHP libraries (ADR-14)
* Selected despite it Requires converting the test and possible other development workflows that have been in use in the Github and Gerrit repositories. The testing and linting approaches might require non trivial adjustments given legacy component repositories have used more dated approaches than WMF Jenkins CI.
* To ensure legacy frontend components will remain independent from the rest of Wikibase frontend logic, some mechanism controlling dependencies should be introduced (e.g. using eslint)
* Legacy frontend libraries can still be published to the npm registry if intended
* Selected despite the fact that building non-Wikibase-dependent frontend applications that make use of some of Wikibase legacy frontend components might become more involved, as keeping the dependency-free components might be more difficult to ensure if they're developed in the same repository as the rest of Wikibase. However, those components do not seem to be actively used in another other application than Wikibase.
* Selected despite this approach might not be applicable for Wikibase Lexeme codebase which also suffers from the Phabricator web scraping.

### Option 2: Move legacy frontend components to Wikimedia Gitlab

#### Consequences

* Relevant components remain integrated into Wikibase as git submodules. No significant changes needed in Wikibase
* Components `data-values/value-view` and `wikibase/javascript-api` are also moved from WMF Gerrit to WMF Gitlab, despite not being affected by Phabricator availability issues, to reduce the number of systems in play
* Requires re-creating the test and other development (e.g. Dependabot) workflows that have been in use on Github and Gerrit repositories
* It might be possible to include some development history (e.g. pull request history) from Github through Gitlab's import functionality
* This approach might be easily reproduced for Wikibase Lexeme codebase

### Option 3: Move legacy frontend components to Wikimedia Gerrit

#### Consequences

* Relevant components remain integrated into Wikibase as git submodules. No significant changes needed in Wikibase
* Requires re-creating the test and other development (e.g. Dependabot) workflows that have been in use on Github repositories
* Requires a change in the development workflow for the moved componented per the different paradigm in Gerrit
* This approach could be also taken for the Wikibase Lexeme codebase, with the caveat of changes needed to the development workflow due to paradigm change to Gerrit's also applying to that codebase


## Advice

* Arthur T advises to move the legacy frontend libraries into Wikibase repository. There is currently no benefits of maintaining those as separate libraries, even if they were there in the past. Having the legacy libraries in the same repository as the most of Wikibase code would make it easier to eventually delete those libraries, rather than keeping them maintained longer then necessary because they're standalone libraries.
* Lucas W advises to move legacy frontend libraries to Wikimedia Gerrit and keep them included as git submodules, as the same approach would also be applicable to other code bases currently integrated through git submodules (also in the Wikibase Lexeme code base)
* Tom A advises to move legacy frontend library to Wikimedia Gerrit and keep them integrated as submodules. Wikimedia Gerrit is preferred over Wikimedia Gitlab as a submodule location as future of Wikimedia Gitlab is unclear, whereas most of WMDE codebases are hosted on Wikimedia Gerrit, so move there would reduce tooling that Wikibase developers have to deal with. Tom suggests to introduce automation that would make the maintenance of the libraries easier (e.g. [Wikimedia LibUp](https://www.mediawiki.org/wiki/LibUp) as an alternative to Dependabot on Github). Tom sees that when legacy libraries are developed in a dedicated repository integrated as submodules it is close to impossible to have the logic in those libraries depend on other Wikibase frontend code, which helps keeping design and dependencies in order. Should the legacy frontend libraries be integrated directly into Wikibase codebase, Tom also advises to introduce a mechanism that would ensure that those boundaries of those libraries don't "leak" and start being dependent on the other Wikibase logic. While favoring the submodule approach Tom recognizes that the solution not using submodules would have the advantage of being simpler.
* Martyn R advises to consider the ease of converting existing CI configuration of legacy frontend libraries as a factor in favour of one option over another. He also sees a benefit of not using submodules as it makes the code base more accessible for developers with limited experience with git.

[phab-frontend-libs-as-submodules]: https://phabricator.wikimedia.org/T174922
[gerrit-github-submodules-through-phab-mirrors]: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/389749
[phab-cloning-timeouts]: https://phabricator.wikimedia.org/T409519
