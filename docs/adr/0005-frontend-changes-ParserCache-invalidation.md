# 5) Invalidate ParserCache on backwards incompatible frontend changes  {#adr_0005}

Date: 2019-08-14

## Status

accepted

## Context
When changes to the markup used on entity pages are made we often end up with outdated content in the ParserCache.
This can result in user facing errors from either the backend code attempting to perform processing on ParserCache output or frontend Javascript attempting to access parts of the DOM that have changed.

 Examples of this are:
 * [T228978] - Backend attempting to fill placeholders that it doesn't expect to be there.
 * [T205330] - Frontend looking for a newly introduced data-attribute that isn't present in historic parser cache entries.

In the not distant past we have solved these problems by introducing a custom [RejectParserCacheValue Hook] in operations/mediawiki-config e.g. (https://gerrit.wikimedia.org/r/c/operations/mediawiki-config/+/463221) to reject cache entries made before deployment time.

We've also done it in stages leaving some invalid entries in the cache to avoid increased load from marking all entries as invalid at once.

While this has worked for us in the past we have typically applied it after discovering a problem. Even then the way we gradually reject historic invalid cached results in user facing errors for some time.

## Decision
We should record things that impact the ParserOutput content (e.g. the version of the code used to generate the ParserOutput) in the parser options. These parser options should then be marked as used in generating the ParserCache key. The RejectParserCacheValue can be used if the two versions of the ParserOutput content cannot coexist and backwards compatibility is not possible.

In this way we will split the ParserCache into old and new versions. An example of this was trialled when introducing new mobile Termbox. See: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/529055.

In the event that we are tracking a new part of the code that doesn't currently have a corresponding option then it may be necessary to ensure it is introduced. The ParserOptions used to calculate the ParserCache key are determined from existing cache entries. Thus on introducing a new key (but not a new key value) a custom RejectParserCacheValue Hook may still be required. See: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/529059 for an example.

An option already exists (but hasn't been used in the last 4 years) on [EntityContent] ([EntityHandler::PARSER_VERSION]) which may be useful for some changes.
However, currently this applies to all entities; in general we usually only make changes to one type of entity at a time so more options will be needed.

## Consequences
If this is followed we should avoid user facing errors when making breaking frontend changes.

We are able to serve without additional hacks both the current and old versions of the ParserCache output. This means we can:
* A/B test the feature
* gradually roll out the feature across entities
* revert a broken feature

However, if the feature is suddenly turned on for all users on all entities then we may see a spike in ParserCache size and application server load as we initially always miss the cache.
Therefore "big bang" breaking changes should be done with caution, and gradual rollouts should be preferred.

[EntityContent]: @ref Wikibase::Repo::Content::EntityContent
[EntityContent::PARSER_VERSION]: @ref Wikibase::Repo::Content::EntityContent::PARSER_VERSION
[RejectParserCacheValue Hook]: https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
[T228978]: https://phabricator.wikimedia.org/T228978
[T205330]: https://phabricator.wikimedia.org/T205330
