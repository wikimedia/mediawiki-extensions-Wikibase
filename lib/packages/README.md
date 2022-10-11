# Wikibase Lib Packages

This directory contains several libraries that are part of Wikibase, but can stand on their own.
Each subdirectory corresponds to one Composer package;
for instance, `lib/packages/wikibase/changes/` contains [wikibase/changes][].
To make development easier these libraries are included in the Wikibase Git repository.
They are directly loaded from there (and not through Composer).
Additionally, we maintain a read-only Git repository of just the Git history of each package,
which is updated with each commit to the Wikibase Git repository using GitHub actions.
For the aforementioned wikibase/changes library, this is [wikimedia/wikibase-changes][].

Development on these libraries is mostly like development on the rest of Wikibase:
make changes to the code (which should become effective immediately on your local wiki),
upload them to Gerrit, let a reviewer merge them.
You can combine modifications to library and non-library code in the same change without issues.
If your change only affects a single library,
you can indicate this in the commit message using a prefix that will be removed when the library Git repository is exported:
for example, [changes: Add README.md and LICENSE][d4ca838935] becomes [Add README.md and LICENSE][3d5ecbc27d].
<!-- TODO once we have more than one library, there should probably be a list of these prefixes here -->

The main difference when working on these libraries is that they should stay independent of MediaWiki and Wikibase.
Don’t use classes or functions from MediaWiki core or other parts of Wikibase.
See also the “decoupling” section below for some examples of what to avoid and what to use instead.

## Extracting new libraries

The process to extract a library was first exercised when extracting the wikibase/changes library, for [T256058][].
The following text attempts to summarize it, but if you run into issues,
try consulting that task and its subtask for more information,
and then please update this documentation as necessary :)

### Decoupling the library from MediaWiki / Wikibase

First of all, the library should be able to function without the rest of MediaWiki and Wikibase.
This means removing references to MediaWiki and Wikibase classes or functions,
or replacing them with other libraries that are available as separate packages;
for instance,
replace `wfTimestamp` with [wikimedia/timestamp][] ([I5bf3abc9c7][]),
replace `\Wikimedia\suppressWarnings()` with [wikimedia/at-ease][] ([Icf9eb3fd94][]),
and so on.

### Move the library to lib/packages/

Create a new directory under `lib/packages/` with the new package name
(probably under `lib/packages/wikibase/`).
Move the code of the library there –
typically, source code (previously under `lib/includes/`) goes into `src/`,
whereas test code (previously under `lib/tests/phpunit/`) goes into `tests/`.

Add the `src/` directory to the `AutoloadNamespaces` in `extension-repo.json` and `extension-client.json`,
so that the classes can still be found by MediaWiki,
as well as to the `.phan/config.php`.
Add the `tests/` directory to `LibHooks::onUnitTestsList()`,
and update the `phpunit.xml.dist` to add `tests/` to the `WikibaseTests` test suite and `src/` to the filter whitelist.
See [I9641aaa277][] for a full example.

### Add new files as needed

The new directory under `lib/packages/`,
which will be the root directory of the extracted Git repository,
needs some extra files:
at least a `composer.json` (with the right dependencies) and a `LICENSE` (GPL2+, like Wikibase),
probably also a `README.md` and a `.gitignore` (suggested entries: `/composer.lock`, `/vendor/`).

### Figure out the git filter-repo incantation

[git filter-repo][] is the tool that we use to extract the Git repository for each library.
Install it, then try running it in a fresh clone of the Wikibase Git repository
(*not* in your usual development clone, though it will refuse to run there anyways by default).
At least the following arguments are necessary:

- `--path`: This controls which paths are included in the filtered repository.
  You should mention each path where the library resided at some point:
  this includes the new (current) location, but also the old one under `lib/includes/`,
  and possibly earlier versions of that
  (for instance, `lib/includes/Changes/` used to be `lib/includes/changes/`).
  Also, add `--path .mailmap` to include the `.mailmap` file.
- `--path-rename`: This controls the new name of the filtered paths,
  and is likely needed for each `--path` entry (except `--path .mailmap`).
  For instance, `--path-rename=lib/includes/Changes:src` specifies that files which used to be in `lib/includes/Changes/` should now be in `src/`;
  `--path-rename lib/packages/wikibase/changes/:` makes `lib/packages/wikibase/changes/` the new repository root.

You probably also want to add:

- `--message-callback`: You can use this to strip a common prefix from commit messages.
  For example, the wikibase/changes library uses `--message-callback 'return re.sub(b"^changes: ", b"", message)'`.
  (The argument is a Python function body; [re][] is the Python regular expression library.)
- `--force`: By default, `git filter-repo` refuses to run on a repository that’s not a fresh clone.
  We’ve found that in the GitHub action which we use to extract the library automatically,
  the repository sometimes doesn’t look like a fresh clone even though it is one.
  Adding `--force` fixes this issue, but should only be needed in the GitHub action,
  not while you’re experimenting with `git filter-repo` locally.

You can find the full commands to extract other libraries in `.github/workflows/main.yaml`.
We found it useful to iterate on the exact `git filter-repo` command,
as well as `composer.json` contents and other details of the extracted repository,
using a command line like the following (to be run in `/tmp` or a similar directory):

```
rm -rf Wikibase/ && git clone --no-local /path/to/extensions/Wikibase/ && (cd Wikibase/ && git-filter-repo --path ... --message-callback ... && composer install && composer test)
```

(`/path/to/extensions/Wikibase/` would be the path to your normal Wikibase clone,
probably in a MediaWiki `extensions/` directory.
It may include some unmerged commits, e.g. a version of the `composer.json` file that you’re still testing.)

### Set up the automatic Git repository extraction

[Create the target Git repository][new repository];
we suggest placing it under the wikimedia organization, not under wmde,
since it will be a read-only Git mirror just like the many Gerrit mirrors.

In order to give the action permission to push to the new repository, you must:
* generate a new SSH key pair
* add the private key to the Wikibase GitHub repository using the [GitHub UI][Wikibase secrets]
  * name the secret <code>SSH_PRIVATE_KEY_<var>repo_name</var></code>
* add the public key as a deployment key to your new repository using the GitHub UI
* destroy this key pair from your local machine

To set up the GitHub action:
* edit `.github/workflows/main.yml` in Wikibase.git to add another job, based on the existing “filter” jobs.
  * make sure to add the key and tweak the secret name appropriately
  * adjust the `targetRepo` and `filterArguments` as necessary
* upload these changes to Gerrit and merge them there

The next time the code is mirrored to GitHub the GitHub action should populate the target repository
automatically.

### Create the Packagist package

Enter the target Git repository URL into the [Packagist new package form][new package];
any developer who is a maintainer on any existing Wikibase package should have the right to create a package.

We recommend following the steps presented by Packagist “without granting Packagist access to your GitHub”.
This involves registering a webhook.

### Set up CI

At this time, we do not have any setup to test the libraries on their own in Wikibase CI.
Instead, we set up Travis CI in the extracted Git repository;
this means we only find out about breakage after merging a change,
but for now that’s good enough.
You can probably copy the `.travis.yml` of the wikibase/changes library without any modifications.

## Creating new libraries

If you’re creating a new library,
the process is similar to the steps above,
but you can skip some parts:
you won’t need to decouple the library from MediaWiki / Wikibase since you’re starting from scratch
(as long as you take care not to introduce new couplings),
and you won’t need to move any code either.
This will also simplify the `git filter-repo` command:
you should only need one `--path` (plus `--path .mailmap`) and one `--path-rename`.

[wikibase/changes]: https://packagist.org/packages/wikibase/changes
[wikimedia/wikibase-changes]: https://github.com/wikimedia/wikibase-changes
[d4ca838935]: https://github.com/wikimedia/mediawiki-extensions-Wikibase/commit/d4ca83893519b7c36274c50ac88a0403bf9a9c93
[3d5ecbc27d]: https://github.com/wikimedia/wikibase-changes/commit/3d5ecbc27dea2cbcae222ebec1dc6835df9becc8
[T256058]: https://phabricator.wikimedia.org/T256058
[wikimedia/timestamp]: https://packagist.org/packages/wikimedia/timestamp
[I5bf3abc9c7]: https://gerrit.wikimedia.org/r/615714
[wikimedia/at-ease]: https://packagist.org/packages/wikimedia/at-ease
[Icf9eb3fd94]: https://gerrit.wikimedia.org/r/615719
[I9641aaa277]: https://gerrit.wikimedia.org/r/616117
[git filter-repo]: https://github.com/newren/git-filter-repo/
[re]: https://docs.python.org/3/library/re.html
[new repository]: https://github.com/new
[Wikibase secrets]: https://github.com/wikimedia/mediawiki-extensions-Wikibase/settings/secrets
[new package]: https://packagist.org/packages/submit
