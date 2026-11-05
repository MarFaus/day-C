# Backend part - API Tests

## Configuration

Upload the api tests to your server.

Before running the tests, configure the `.env` file in this directory
and provide correct database credentials and the URL where your API is available.

Before the tests get executed, you can see the current configuration printed out in the terminal.

## Run tests

The backend app needs to be running for the tests.

Tests can only be run on the server.

Run all tests:
```bash
composer test
```

Run a single test:
```bash
composer test -- --filter B2EventDetailTest
```
