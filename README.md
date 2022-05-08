# Laravel Exact  Client
![Run phpunit](https://github.com/picqer/exact-php-client/workflows/Run%20phpunit/badge.svg)

PHP client library for the Exact Online API. This client lets you integrate with Exact Online, for example by:
- creating and sending invoices, 
- add journal entries, 
- or upload received invoices.

Wanted to use Exact online in your Laravel application.
We build a easy Laravel wrapper based on the 
https://github.com/picqer/exact-php-client

## installation:
First, make sure to add the following keys to your .env file.

```dotenv
EXACT_CLIENT_ID
EXACT_CLIENT_SECRET
EXACT_WEBHOOK
```

The package made the Oauth connection simple and stores the tokens in DB. 
This allows you to use the client with multiple users. The focus was to support the Exact webhooks to trigger laravel events to make it is easy to integrate with your application.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](https://choosealicense.com/licenses/mit/)