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

### Development Troubleshooting
#### Build failing because checking dist and built dist don't match
If you see errors like:
`Files ./dist/tainted-ref.common.js and /tmp/dist/tainted-ref.common.js differ` in your jenkins builds then you can try:
* checking you staged all changes for commit. For example, /dist/tainted-ref.common.js is treated as a binary file and hence is ignored
by `git add -p`
* rebuilding again following the above Compiles and minifies for production
* resetting your node-modules to match those checked in with `docker-compose run --rm node npm run ci`
