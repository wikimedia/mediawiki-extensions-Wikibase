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

### Lints and fixes files
```
docker-compose run --rm node npm run lint
```
