# Documentation

 - Published at: https://doc.wikimedia.org/Wikibase/master/php/
 - Generated with Doxygen ([manual](http://www.doxygen.nl/manual))
 - Configuration: Doxyfile (in the root of this repo)

[docs/index.md](index.md) is the main entry point to the generated documentation site.

### Generating doc site locally

You can use the composer command ```composer doxygen-docker```.
This will generate the static HTML for the docs site in the `docs/php` directory of this repo.

The command uses the [`docker-registry.wikimedia.org/releng/doxygen:latest`][releng/doxygen] docker image ([releng/doxygen source]).

### Structure

The structure of the site is dictated by the [`@subpage`] relations.

The top level of the tree of pages is [docs/index.md](index.md).

### Markdown

Markdown documentation: http://doxygen.nl/manual/markdown.html

**Linking to other markdown files**

The easiest way to link to another markdown file it to use md_ prefixed reference and add the full link to the bottom of the file.

NOTE: special characters, including dash (`-`), are replaces with an underscore (`_`) - see example below.

```md
Basic link to the [Data Access] page. The text between the square brackets is used as the link text.\n
Link to the [Wikibase Client][wb-client] page with custom link text (markdown version).\n
Link to the [wb-repo] page with custom link text (`@ref` version).\n

[Data Access]: @ref md_docs_components_data_access
[wb-client]: @ref md_docs_components_client
[wb-repo]: @ref md_docs_components_repo "Wikibase Repo"
```

The example above will render as between the horizontal lines below:

- - -
Basic link to the [Data Access] page. The text between the square brackets is used as the link text.\n
Link to the [Wikibase Client][wb-client] page with custom link text (markdown version).\n
Link to the [wb-repo] page with custom link text (`@ref` version).\n

[Data Access]: @ref md_docs_components_data_access
[wb-client]: @ref md_docs_components_client
[wb-repo]: @ref md_docs_components_repo "Wikibase Repo"
- - -

This allows the main text to be easily read while only having to specify the target once.
This also avoids manually maintaining [header attributes] at the top of files.

### Diagrams

Doxygen allows you to incorporate multiple types of diagrams in your docs.

The ones we currently use are listed below:

**dot**
 - Command docs: http://www.doxygen.nl/manual/commands.html#cmddot
 - Spec docs: https://graphviz.gitlab.io/_pages/pdf/dotguide.pdf
 - Visual tool: http://viz-js.com/
 - Example: @ref md_docs_storage_terms

**msc**
 - Command docs: http://www.doxygen.nl/manual/commands.html#cmdmsc
 - Spec docs: http://www.mcternan.me.uk/mscgen/
 - Visual tool: https://mscgen.js.org/
 - Example: @ref md_docs_topics_change-propagation

[header attributes]: http://doxygen.nl/manual/markdown.html#md_header_id
[releng/doxygen]: https://docker-registry.wikimedia.org/releng/doxygen/tags/
[releng/doxygen source]: https://gerrit.wikimedia.org/r/plugins/gitiles/integration/config/+/refs/heads/master/dockerfiles/doxygen/
[`@subpage`]: https://doxygen.nl/manual/commands.html#cmdsubpage
