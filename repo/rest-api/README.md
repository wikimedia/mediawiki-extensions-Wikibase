# Wikibase REST API

## Configuration

Enable the feature toggle:
```php
$wgWBRepoSettings['restApiEnabled'] = true;
```

## OpenAPI Specification

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

API specs can be bundled into a single file using npm `build:spec` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm run build:spec
```

Autodocs can be generated from the API specification using npm `build:docs` script, e.g. by running:

```
docker run --rm --user $(id -u):$(id -g) -v $PWD:/app -w /app node:16 npm run build:docs
```

The autodocs and/or bundled specification OpenAPI files are generated to the `dist` directory.

## Development

* @subpage rest_adr_index
