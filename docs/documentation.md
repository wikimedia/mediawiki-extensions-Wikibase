# Documentation

 - Published at: https://doc.wikimedia.org/Wikibase/master/php/
 - Generated with Doxygen ([manual](http://www.doxygen.nl/manual))
 - Configuration: `Doxyfile` (in the root of this repo)

[docs/index.md](index.md) is the main entry point to the generated documentation site.

### Generating doc site locally

You can use the composer command ```composer doxygen-docker```.
This will generate the static HTML for the docs site in the `docs/php` directory of this repo.

This command uses the [`docker-registry.wikimedia.org/releng/doxygen:latest`][releng/doxygen] docker image ([releng/doxygen source]).

### Structure

The structure of the site is dictated by the [`@subpage`] relations.

The top level of the tree of pages is [docs/index.md](index.md).

### Markdown

Markdown documentation: http://doxygen.nl/manual/markdown.html

#### Linking to other markdown files

The easiest way to link to another markdown file is to use a [reference link] and add the full
reference to the [Automatic Header Id] at the bottom of the file.\n
It's recommended to order the reference links at the bottom of the page in alphabetical order for easier parsing.\n
**NOTE**: Reference links are case-insensitive (see [troubleshooting](@ref case-insensitive-links)  below).

```md
Basic link to the [Data Access] page. The text between the square brackets is used as the link text.\n
Link to the [Wikibase Client][wb-client] page with custom link text (markdown version).\n
Link to the [wb-repo] page with custom link text (`@ref` version).\n

[Data Access]: @ref docs_components_data-access
[wb-client]: @ref docs_components_client
[wb-repo]: @ref docs_components_repo "Wikibase Repo"
```

The example above will render as between the horizontal lines below:

- - -
Basic link to the [Data Access] page. The text between the square brackets is used as the link text.\n
Link to the [Wikibase Client][wb-client] page with custom link text (markdown version).\n
Link to the [wb-repo] page with custom link text (`@ref` version).\n

[Data Access]: @ref docs_components_data-access
[wb-client]: @ref docs_components_client
[wb-repo]: @ref docs_components_repo "Wikibase Repo"
- - -

This allows the main text to be easily read while only having to specify the target once.
This also avoids manually maintaining [Header Id] attributes at the top of files.

#### Automatic Header Id {#automatic-header-id}

Doxygen 1.9 changed the way markdown files are referenced internally (see this [GitHub bug]) by
replacing all special characters with an underscore (`_`). This caused many references to files that
had a dash (`-`) in their filename to break.

In order to provide stable references to markdown files, without having to manually maintain [Header Id] attributes, an [input filter] script was created at `build/doxygen-markdown-auto-header-id-filter.sh`. Using the [FILTER_PATTERNS] option, this script is executed on each file with a `.md` extension just before Doxygen parses it, silently adding a [Header Id] attribute based in its filepath and filename. These [Header Id] attributes are only seen by Doxygen and never written to file.

For this script to automatically add a [Header Id], the following rules must be met:

1. The file must end in `.md`
2. The file must contain a markdown header in the format `<hash><space><header text>`, e.g., `# Example header`
3. The header must be on the first line of the markdown file
4. The first line must not contain a [Header Id] \(so that you can provide your own one if required)

The script creates the [Header Id] in the format `<path_to_file_from_Wikibase_root><FilenameWithoutExtension>`. For example, to link to the "docs/topics/change-propagation.md" file use `@ref docs_topics_change-propagation`. The same reference works for the [`@subpage`] command as well.

### Diagrams

Doxygen allows you to incorporate multiple types of diagrams in your docs.

The ones we currently use are listed below:

**dot**
 - Command docs: http://www.doxygen.nl/manual/commands.html#cmddot
 - Spec docs: https://graphviz.gitlab.io/_pages/pdf/dotguide.pdf
 - Visual tool: http://viz-js.com/
 - Example: @ref docs_storage_terms

**msc**
 - Command docs: http://www.doxygen.nl/manual/commands.html#cmdmsc
 - Spec docs: http://www.mcternan.me.uk/mscgen/
 - Visual tool: https://mscgen.js.org/
 - Example: @ref docs_topics_change-propagation

### Troubleshooting

#### Reference links are case-insensitive {#case-insensitive-links}

```
[IdGenerator] will link to the Wikibase docs about the `IdGenerator` interface.\n
[idGenerator] will **NOT** link to the Wikibase option `idGenerator`, but instead to the `IdGenerator` interface.\n
[repo_idGenerator] will link to the Wikibase option `idGenerator`.

[IdGenerator]: @ref Wikibase::Repo::Store::IdGenerator
[idGenerator]: @ref repo_idGenerator "This is ignored"
[repo_idGenerator]: @ref repo_idGenerator "idGenerator"
```

The example above will render as between the horizontal lines below:

- - -
[IdGenerator] will link to the Wikibase docs about the `IdGenerator` interface.\n
[idGenerator] will **NOT** link to the Wikibase option `idGenerator`, but instead to the `IdGenerator` interface.\n
[repo_idGenerator] will link to the Wikibase option `idGenerator`.

[IdGenerator]: @ref Wikibase::Repo::Store::IdGenerator
[idGenerator]: @ref repo_idGenerator "This is ignored"
[repo_idGenerator]: @ref repo_idGenerator "idGenerator"
- - -


[Automatic Header Id]: @ref automatic-header-id
[FILTER_PATTERNS]: https://doxygen.nl/manual/config.html#cfg_filter_patterns
[GitHub bug]: https://github.com/doxygen/doxygen/issues/8377
[Header Id]: http://doxygen.nl/manual/markdown.html#md_header_id
[input filter]: https://doxygen.nl/manual/config.html#cfg_input_filter
[reference link]: https://doxygen.nl/manual/markdown.html#md_reflinks
[releng/doxygen]: https://docker-registry.wikimedia.org/releng/doxygen/tags/
[releng/doxygen source]: https://gerrit.wikimedia.org/r/plugins/gitiles/integration/config/+/refs/heads/master/dockerfiles/doxygen/
[`@subpage`]: https://doxygen.nl/manual/commands.html#cmdsubpage
