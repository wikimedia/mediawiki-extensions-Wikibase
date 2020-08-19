# 11) declare strict_types Rollout {#adr_0011}

Date: 2020-04-23

## Status

accepted

## Context

The first patch introducing a usage of `declare( strict_types = 1 );` has [just been merged into Wikibase.git](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Wikibase/+/591971/).
declare strict_types is also already used in some of our composer libraries.

 - Blog post explaining the feature: https://blog.programster.org/php-strict-types
 - Docs for strict types: https://www.php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration.strict

In a nut shell, declaring strict types means that PHP will no longer automatically cast wrong scalar variables to the expected type, methods will require the correct type to be called.
"Strict typing applies to function calls made from within the file with strict typing enabled, not to the functions declared within that file. If a file without strict typing enabled makes a call to a function that was defined in a file with strict typing, the caller's preference (weak typing) will be respected, and the value will be coerced."

**Why is strict typing beneficial**

 - avoid (subtle) bugs
 - make it easier to reason with code
 - [open up the possibility of (future) performance optimization at the language level](https://stackoverflow.com/questions/32940170/are-scalar-and-strict-types-in-php7-a-performance-enhancing-feature/32943651#32943651)

In order to proceed with use of strict_types we need to have an agreed upon strategy for rollout and maintenance.

PHP Codesniffer already has a Generic.PHP.RequireStrictTypes sniff implemented that we can use to enforce a requirement of strict types declaration.

Code for estimation of completion:
```
EST_DONE=$(grep --exclude-dir={vendor,node_modules,.idea,.phan} --include=\*.php -rnw . -e "strict_types" | wc -l) \
&& EST_TODO=$(find ./ -type f -name "*.php" | wc -l) \
&& EST_PRECENT=$(( $EST_DONE*100/EST_TODO )) \
&& echo ${EST_DONE}/${EST_TODO} = ${EST_PRECENT}%
```

## Decision

We will progressively rollout and use strict_types across all PHP code as we touch the code in regular work.
This includes test classes and classes that may not immediately appear to benefit having strict types enabled (doesn't use scalar type hints).
Any new code should always use strict_types and this should ideally be identified in code review.

We will continue to assess our advancement in rollout through time using the code snippet in the context section.
The [phabricator ticket](https://phabricator.wikimedia.org/T251382) should be updated so that progress is tracked.

At a point that we deem sensible we will enable the sniff for RequireStrictTypes, adding any missing files or directories to the exclusion list for the sniff.

Once strict_types is used everywhere this ADR will be deprecated.

## Consequences

We will progressively move toward strict types everywhere.

We will not perform any mass bulk changes, meaning we will look at the implications of the code that we add strict types to.
