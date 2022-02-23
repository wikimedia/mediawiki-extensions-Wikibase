# Wikibase REST API

## Configuration

Enable the feature toggle:
```php
$wgWBRepoSettings['restApiEnabled'] = true;
```

## API Specification

REST API specification is provided using OpenAPI specification in `specs` directory.

Specification can "built" (i.e. compiled to a single JSON OpenAPI specs file) and validated using provided npm scripts.

To modify API specs, install npm dependencies first, e.g. using the following command:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm install
```

API specs can be validated using npm `test` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm test
```

API specs can be bundled into a single file using npm `build` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm run build
```
