# data-bridge

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
