# 16) Use GitHub Actions as secondary CI system for Wikibase {#adr_0016}

Date: 2021-01-06

## Status

accepted

## Context

The Wikibase CI which runs on WMF Jenkins is currently "augmented" by running php unit tests in a variety of additional configurations (e.g. non-English wiki, repo/client-only environment, etc) on legacy Travis CI infrastructure, via the GitHub mirror of our Gerrit code repository, see [https://travis-ci.org/github/wikimedia/Wikibase](https://travis-ci.org/github/wikimedia/Wikibase).

The Travis CI features we currently use include:
* PHP installed (multiple versions),
* MySQL running,
* notifications via
  * email,
  * IRC,
* composer cache.

Due to a change in their business model, the travis-ci.org service is being phased out and replaced by (paid) travis-ci.com. Since we have no intention of dropping the extended CI testing, there are several options how to handle the situation:
* migrate additional CI for Wikibase to some other CI infrastructure, e.g. _GitHub Actions_,
* negotiate with the WMF changing the Travis CI plan to a paid one which would have unlimited/less limited resources available,
* migrate additional CI for Wikibase to additional jobs on WMF Jenkins CI.

## Decision

We will migrate the additional CI for Wikibase to the GitHub CI infrastructure, using _GitHub Actions_.

Reasons: The GitHub mirror of the Wikibase repository exists already and all of the Travis CI features we have been using so far, are available on _GitHub Actions_:
* [setup-php](https://github.com/shivammathur/setup-php),
* MySQL pre-installed on the runner or use [MySQL service container](https://firefart.at/post/using-mysql-service-with-github-actions/) (to be investigated),
* notifications via
  * [email](https://docs.github.com/en/free-pro-team@latest/github/managing-subscriptions-and-notifications-on-github/configuring-notifications) (built-in),
  * [notify-irc](https://github.com/rectalogic/notify-irc) + [failure() condition](https://docs.github.com/en/free-pro-team@latest/actions/reference/context-and-expression-syntax-for-github-actions#failure),
* [cache](https://github.com/actions/cache).

## Consequences

Since most of our Travis CI is a collection of shell scripts, it will mostly work the same under GitHub Actions and migrating is a relatively low-effort solution for the short or mid term; in the long term, the Wikimedia migration to GitLab will presumably change a lot in our CI processes.
