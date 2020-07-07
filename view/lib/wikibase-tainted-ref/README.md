# Tainted references

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

* `CSR_PORT` is the port at which you can reach the development server on your machine to live-preview changes to the application
* `STORYBOOK_PORT` is the port at which you can reach the storybook server on your machine to live-preview changes in the component library
* `NODE_ENV` is the environment to set for node.js


### Compiles and minifies for production

NOTE: This must be run after making changes to the javascript/typescript files and before committing so that the latest build files will be available in /dist.

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

### Lints files for code style violations
```
docker-compose run --rm node npm run test:lint
```

### Storybook
```
docker-compose run --rm node npm run storybook
```

### Entry point
The application entry point is `src/main.ts`. This is the common first point of the application both as it is run in
dev mode (the hot refreshing setup for quick development) and as part of a Wikibase entity page.

The code resulting from main.ts is build into `dist/tainted-ref.common.js`

The code in `src/main.ts` is executed by `src/mock-entry.ts` when in dev mode.

It is executed by `src/init.ts` when part of a Wikibase page.

`src/init.ts` contains code that is bound to the window environment present in Mediawiki/Wikibase. It uses [ResourceLoader](https://www.mediawiki.org/wiki/ResourceLoader)
to load the rest of the application.

`src/tainted-ref.init.ts` is built into `dist/tainted-ref.init.js` and runs the exported function of init.ts after page load.


### Hooks
This application listens to hooks fired by the Mediawiki UI and then proceeds to handle them. e.g. by dispatching vuex actions
or running other code.

How hooks are handled is defined in `src/MWHookHandler.ts`

* `addSaveHook`: Sets the tainted state and track edits made on statement.
* `addStartEditHook`: Set the edit state to true when edit button is clicked.
* `addStopEditHook`: set the edit state to false after each edit (could be a saved or canceled edit).

You can read about the Wikibase specific hooks at: extensions/Wikibase/docs/topics/hooks-js.md

Some of these hooks send payloads containing object built from the Javascript Wikibase Datamodel. See Below.

### Wikibase Datamodel
This application uses the payload of hooks fired by the Wikibase ui. These objects are defined by the [Javascript Wikibase Datamodel](https://github.com/wmde/WikibaseDataModelJavaScript)
We depend on this for some functionality but due to the way the library is written we don't import it via npm and instead rely on it at run-time.
For tests it is mocked.

### Development Troubleshooting
#### Build failing because checking dist and built dist don't match
If you see errors like:
`Files ./dist/tainted-ref.common.js and /tmp/dist/tainted-ref.common.js differ` in your jenkins builds then you can try:
* checking you staged all changes for commit. For example, /dist/tainted-ref.common.js is treated as a binary file and hence is ignored
by `git add -p`
* rebuilding again following the above Compiles and minifies for production
* resetting your node-modules to match those checked in with `docker-compose run --rm node npm run ci`
