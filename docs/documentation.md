# Documentation

 - Published at: https://doc.wikimedia.org/Wikibase/master/php/
 - Generated with Doxygen ([manual](http://www.doxygen.nl/manual))
 - Configuration: Doxyfile (in the root of this repo)

Mainpage.md is the main entry point to the generated documentation site.

### Generating doc site locally

You can use the composer command ```composer doxygen-docker```.
This will generate the static HTML for the docs site in `docs/php` in this repo.

The command uses the `docker-registry.wikimedia.org/releng/doxygen:latest` docker image.

### Structure

The structure of the site is dictated by the subpage relations.

The top level of the tree of pages is Mainpage.md

### Markdown

Markdown documentation: http://doxygen.nl/manual/markdown.html

**Linking to other markdown files**

The easiest way to link to another markdown file it to use md_ prefixed reference and add the full link to the bottom of the file.

```md
Foo bar baz [wbc_entity_usage] talks about the [wbc_entity_usage] table.

[wbc_entity_usage]: @ref md_docs_sql_wbc_entity_usage
```

This allows the main text to be easily read while only having to specify the target once.
This also avoid manually maintaining [header attributes](http://doxygen.nl/manual/markdown.html#md_header_id) at the top of files.

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
