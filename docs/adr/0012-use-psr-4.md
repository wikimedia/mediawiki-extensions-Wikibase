# 12) PSR-4 and maintenance scripts {#adr_0012}

Date: 2020-06-15

## Status

accepted

## Context

PHP’s [autoloading][] mechanism allows for a class or interface to be loaded from a file automatically when it is first needed,
rather than having to `require_once` the correct file to ensure that it has been loaded.

The industry standard way to autoload classes is to follow the [PSR-4][] standard.
It took a while to make Wikibase fully follow this standard
(more details may be found [on MediaWiki.org][Wikibase/History/Autoloading]),
but once the option was available via the `AutoloadNamespaces` in `extension.json`,
there was no question of whether we wanted to do this:
it was just a matter of putting in the work.

The only real conceptual issue in migration were the maintenance scripts.
MediaWiki maintenance scripts are traditionally stored in files beginning with lowercase letters –
as in MediaWiki’s `dumpLinks.php`, so also in Wikibase’s `dumpJson.php`.
However, the classes inside those files begin in uppercase, like other classes (`DumpLinks`, `DumpJson`).
This was not a problem back when we used other autoloading mechanisms,
but PSR-4 requires that the class and file name match exactly
(aside from the `.php` file name extension),
including in case.
This means that the maintenance script classes could not be autoloaded via PSR-4 in their current form.
Most of the maintenance scripts did not *need* to be autoloaded at all.
A few were referenced by class name in the `DatabaseSchemaUpdater` or in tests.

## Considered actions

We considered several solutions for the maintenance scripts:

1. Put all maintenance scripts in `AutoloadClasses`
   (in the JSON files, not `$wgAutoloadClasses` in the PHP entry points),
   so that they can still be autoloaded.
   This would have more or less reflected the behavior of the generated `autoload.php` files.
2. Put only the necessary maintenance scripts in `AutoloadClasses`.
   “Necessary” would be determined by searching for references to the class names.
3. Like 2, but put maintenance scripts that only need to be autoloadable for unit tests
   in `TestAutoloadClasses` rather than `AutoloadClasses`.
   This keeps the production autoloader a bit smaller,
   but also introduces a difference between production and test environments,
   and it’s not clear if putting non-test classes in `TestAutoloadClasses` is wise.
4. Like 2, but load maintenance scripts that are only needed for unit tests directly in those unit tests,
   using `require_once`: `AutoloadClasses` would only contain classes needed for the `DatabaseSchemaUpdater`.
   Compared to 3, using `require_once` in the individual unit tests rather than `TestAutoloadClasses`
   reduces the risk that other code inadvertently depends on the maintenance scripts,
   since the other code can no longer rely on the maintenance scripts being loadable.
5. Actually fix the maintenance scripts.
   Move them to the initial uppercase versions of their file names,
   then (for compatibility) add initial lowercase PHP files again which delegate to the real files,
   probably after printing a warning that the user should start using the uppercase names instead.
   However, it’s unclear if this is worth the effort of updating all the scripts;
   also, on Windows it would not be possible to have both of these sets of files checked out at the same time,
   since file names are case insensitive there,
   and the real maintenance script files would therefore collide with the backwards compatibility files.

At the time that we made this decision (June 2020),
we had only recently suffered from [an incident][wb_items_per_site dropped]
where an important (though secondary) database table was dropped,
causing issues across Wikidata that took weeks to clean up.
Because this incident was partly related to maintenance-like code running when it shouldn’t have,
we were anxious about touching the maintenance scripts at all,
and at the same time keen to limit the situations in which they could be loaded.

## Decision

Use PSR-4 for all regular source code files.
Every file that resides under an `includes/` or `src/` directory must conform to PSR-4.

For the maintenance scripts,
add the two scripts that are needed for `DatabaseSchemaUpdater` to `AutoloadClasses`.
Scripts that are needed for unit tests are loaded via `require_once` directly in those unit tests.
(This corresponds to option 4 above.)

Encourage the use of PSR-4 compliant, initial upper case file names for any future new maintenance scripts.

## Consequences

The move of all Wikibase classes to the correct namespace (according to their directory, which was usually more accurate)
was completed shortly before the submission of this ADR.
There is no mechanism to load non-compliant files,
so the risk of accidentally introducing new classes that do not conform to PSR-4 is negligible
(you’d notice the error as soon as you tried to use the class).

Since both of the maintenance scripts that need to be autoloadable for `DatabaseSchemaUpdater` are related to the `wb_terms` table,
we anticipate that we will be able to remove them more or less soon,
once we completely remove support for the old term storage from Wikibase.
At that point, we will not need any more `AutoloadClasses` in all of Wikibase.

We also expect that,
once the RFC to [create a proper command-line runner for MediaWiki maintenance tasks][T99268] is resolved,
the situation for maintenance scripts will change again.
It may be that at that point, they will need to be autoloadable;
on the other hand, it may then no longer be necessary or customary to directly refer to them by file name,
which would make it more feasible to move them to the correct file name as required by PSR-4.
We shall see.

[autoloading]: https://www.php.net/manual/en/language.oop5.autoload.php
[PSR-4]: https://www.php-fig.org/psr/psr-4/
[Wikibase/History/Autoloading]: https://www.mediawiki.org/wiki/Wikibase/History/Autoloading
[wb_items_per_site dropped]: https://wikitech.wikimedia.org/wiki/Incident_documentation/20200407-Wikidata%27s_wb_items_per_site_table_dropped
[T99268]: https://phabricator.wikimedia.org/T99268
