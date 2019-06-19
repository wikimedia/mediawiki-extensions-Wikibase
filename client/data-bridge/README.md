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

### Run your tests
```
docker-compose run --rm node npm run test:unit
```
Jest can watch the filesystem and run the tests affecting your files changed after the last commit with:
```
npm run test:unit -- --watch
```
Since docker isolates node from git, it is not possible to use information about which files changed since the last commit.
However, one can still automatically run all tests when a file changes:
```
docker-compose run --rm node npm run test:unit -- --watchAll
```

### Lints and fixes files
```
docker-compose run --rm node npm run lint
```

### Storybook
```
docker-compose run --rm node npm run storybook
```
