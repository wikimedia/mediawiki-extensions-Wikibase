The **Wikidata Bridge** (formerly known as “client editing”) is a project aiming to make it possible to edit Wikidata’s data directly from Wikipedia.

## Project setup
```
# ensure the node user uses your user id, so you own generated files
docker-compose build --build-arg UID=$(id -u) --build-arg GID=$(id -g) node
docker-compose run --rm node npm install
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

## Debugging

Bridge augments MediaWiki pages and uses some of its modules to do its work.
It uses [`mw.track`](https://www.mediawiki.org/wiki/ResourceLoader/Core_modules#mw.track) to publish analytics events (under the `MediaWiki.wikibase.client.databridge` topic) which can be collected as [statistics](https://www.mediawiki.org/wiki/Manual:How_to_debug/en#Statistics).

For debugging of a specific scenario you can also subscribe to those events locally in your browser via e.g.

```javascript
mediaWiki.trackSubscribe( '', console.log );
```
