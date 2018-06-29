# Keboola HTTP Extractor
 
[![Build Status](https://travis-ci.org/keboola/http-extractor.svg?branch=master)](https://travis-ci.org/keboola/http-extractor)
[![Maintainability](https://api.codeclimate.com/v1/badges/dbd6232439360319f152/maintainability)](https://codeclimate.com/github/keboola/http-extractor/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/dbd6232439360319f152/test_coverage)](https://codeclimate.com/github/keboola/http-extractor/test_coverage)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/keboola/gmail-extractor/blob/master/LICENSE.md)


Download files from any public URL to `/data/out/files`. 

## Configuration options

- `baseUrl` (required) -- common part of URL
- `path` (required) -- path part of URL (futureproof to allow row configs)
- `maxRedirects` (optional) -- maximum number of redirects to follow

### Sample configurations

#### Minimal config

```json
{
    "parameters": {
        "baseUrl": "https://www.google.com/",
        "path": "favicon.ico"
    }
}
```

This will save Google favicon into `/data/out/files/favicon.ico`. 

## Development

- Install Composer packages

```
docker-compose run --rm dev composer install --prefer-dist --no-interaction
```

- Get contents for `data/` directory using [Sandbox API call](https://developers.keboola.com/extend/common-interface/sandbox/) (replace `YOURTOKEN` with your Storage API token). 

```
curl --request POST --url https://syrup.keboola.com/docker/sandbox --header 'Content-Type: application/json'   --header 'X-StorageApi-Token:YOURTOKEN' --data '{"configData": { "parameters": {"baseUrl": "https://www.google.com/","path": "favicon.ico"}}}'
```


- Run the extractor 

```
docker-compose run --rm dev
```

### Tests
Run the complete CI build

```
docker-compose run --rm dev composer ci
```

or just the tests

```
docker-compose run --rm dev composer tests
```
