# 20) Keep Wikibase master compatible with Mediawiki Core master {#adr_0020}

Date: 2021-06-30

## Status

accepted

## Context

Wikibase is currently only tested to be compatible with Mediawiki core and other MediaWiki extensions for:
- sets of commits that are the HEAD of the master branch at one point in time
- cut releases: either for the alpha, weekly train releases (i.e. releases delivered on WMF wikis), or the twice-yearly(ish) official MediaWiki releases

We want to give 3rd party Wikibase users regular 6 monthly releases.

MediaWiki release are currently not that regular.

We intend to produce new Wikibase features on a more regular basis than 6 monthly and we want these to be available to users
as soon as possible. Both for users to benefit from new features but also to shorten our development feedback cycles.

To be able to release Wikibase at any point in time such that it remains possible to apply security patches to MediaWiki core and to
any extensions that are not maintained by WMDE these releases need to be compatible with a "cut release" of MediaWiki.

To ensure this compatibility there are two options:
- Backport Wikibase features, and any dependent patches to the last stable release of MediaWiki before releasing.
  This was performed for the upcoming wmde1 release of Wikibase where patches made against master (i.e. 1.36 alpha releases) were backported to the Wikibase 1.35 branch
- Keep Wikibase `master` compatible with the last stable release of MediaWiki

Doing the backporting results in leaving a possibly unknown amount of work for whoever is to make the release and adds lots of uncertainty.
The additional backporting effort will need to be repeated for all upcoming Wikibase releases.
This approach also means maintaing two different (even if only in the sense of git history) versions of the same functionality.

Keeping Wikibase compatible with last stable MediaWiki adds to every developer's workload.
It may result in having to delay using new features in MediaWiki or having to write a compatibility layer in order to use them.
It may result in developers inadvertently using a new feature and then discovering they need to either
write some backwards compatibility layer for it or put that new feature behind a flag.

Doing so may result in being more decoupled from MediaWiki in the long run.

The overhead of the additional development effort will possibly be increasingly lower
the more code has been written that way, and the more Wikibase releases have been published following this approach.

Keeping Wikibase compatible with last stable MediaWiki would likely mean avoiding the "double work" effort
of developing the feature against the master branch, and "backporting" it to be compatible with the last stable version of Mediawiki
needed for the Wikibase release for non-WMF users.

## Decision

Only ensure Wikibase remains compatible with MediaWiki master. Do not enforce compatibility with last stable version of MediaWiki.

## Consequences
- When we cut the next Wikibase release in Fall 2021 we should allow for time to:
  - determine which features need to be backported
  - backport the feature code and any intermediate code upon which it depends
