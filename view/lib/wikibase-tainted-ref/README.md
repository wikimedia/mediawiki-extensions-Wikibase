# tainted references

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
