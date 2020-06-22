The **Wikidata Bridge** (formerly known as “client editing”) is a project aiming to make it possible to edit Wikidata’s data directly from Wikipedia.

## Project setup
```
# ensure the node user uses your user id, so you own generated files
docker-compose build --build-arg UID=$(id -u) --build-arg GID=$(id -g) node
docker-compose run --rm node npm install
docker-compose run --rm node npm run prepare
```

### Compiles and hot-reloads for development
```
docker-compose up
```

This uses the values from `.env` for configuration - create a `.env.local` file if you desire diverging values.

* `CSR_PORT` is the port at which you can reach the development server on your machine to live-preview changes to the application. This allows development outside of MediaWiki, using a simulated environment as configured in `src/dev-entry.ts`.
* `STORYBOOK_PORT` is the port at which you can reach the storybook server on your machine to live-preview changes in the component library
* `NODE_ENV` is the environment to set for node.js


### Compiles and minifies for production
```
docker-compose run --rm node npm run build
```

Bridge is built with Vue's [modern mode](https://cli.vuejs.org/guide/browser-compatibility.html#modern-mode) to ship light-weight JavaScript to capable browsers. MediaWiki chooses one or the other distribution using `client/resources/wikibase.client.data-bridge.app.js`.

### Automatically fix code style violations
```
docker-compose run --rm node npm run fix
```

### Run all code quality tools
```
docker-compose run --rm node npm run test
```

### Run jest unit tests
```
docker-compose run --rm node npm run test:unit
```
Jest can watch the filesystem and run the tests affecting your files changed after the last commit with:
```
docker-compose run --rm node npm run test:unit -- --watch
```

### Lints files for code style violations
```
docker-compose run --rm node npm run test:lint
```

### Storybook
```
docker-compose run --rm node npm run storybook
```

### Git helpers

To automatically resolve merge conflicts within `dist/` by doing a rebuild, run the following command:

```sh
npx npm-merge-driver install \
    --driver-name bridge-rebuild \
    --driver 'npx npm-merge-driver merge %A %O %B %P -c \"cd -- \\\"\$(git rev-parse --show-toplevel)/client/data-bridge\\\" && npm ci && npm run build:app\"' \
    --files /client/data-bridge/dist/{data-bridge.{app{,.modern},init}.js,css/data-bridge.app.css}
```

This is very useful when rebasing unmerged changes, for example.

To automatically prefix messages of Bridge-related commits with the keyword “bridge:”,
put the following in `.git/hooks/prepare-commit-msg` and ensure the file is executable:

```sh
#!/usr/bin/env bash

case $2 in
    message|template|commit|'')
        if ! git diff --cached --exit-code -- '://client/data-bridge/*' > /dev/null &&
                git diff --cached --exit-code -- ':!/client/data-bridge/*' > /dev/null; then
            # differences in data bridge and none outside it,
            # prefix the message with "bridge: " if necessary
            sed -i '
                1 {
                    # add bridge: prefix
                    s/^/bridge: /
                    # remove duplicate prefix
                    s/ bridge://g
                    # remove bridge: again before rebase.autosquash prefixes
                    s/^bridge: \(fixup!\|squash!\)/\1/
                }
                ' -- "$1"
        fi
        ;;
esac
```

## Debugging

Bridge augments MediaWiki pages and uses some of its modules to do its work.
It uses [`mw.track`](https://www.mediawiki.org/wiki/ResourceLoader/Core_modules#mw.track) to publish analytics events (under the `MediaWiki.wikibase.client.databridge` topic) which can be collected as [statistics](https://www.mediawiki.org/wiki/Manual:How_to_debug/en#Statistics).

For debugging of a specific scenario you can also subscribe to those events locally in your browser via e.g.

```javascript
mediaWiki.trackSubscribe( '', console.log );
```
