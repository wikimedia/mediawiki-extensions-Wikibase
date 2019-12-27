# Documentation {#documentation}

 - General documentation: http://www.doxygen.nl/manual
 - Published at: https://doc.wikimedia.org/Wikibase/master/php/
 - Configuration: Doxyfile (in the root of this repo)

Mainpage.md is the main entry point to the generated documentation site.

### Generating doc site locally

You can use the composer command ```composer doxygen-docker```.
This will generate the static HTML for the docs site in `docs/php` in this repo.

The command uses the `docker-registry.wikimedia.org/releng/doxygen:latest` docker image.

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
 - Example: @ref md_docs_topics_change-propogation
