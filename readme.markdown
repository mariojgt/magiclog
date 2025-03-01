# Laravel Request Logger

A lightweight, feature-rich HTTP request logging and dashboard package for Laravel.

Images

![Dashboard](https://github.com/mariojgt/magiclog/blob/main/art/image02.png)
![Dashboard](https://github.com/mariojgt/magiclog/blob/main/art/image01.png)

## Features

- 🚀 Lightweight and performant request logging
- 📊 Beautiful, responsive dashboard
- 🔒 Configurable logging options
- 📈 Detailed request insights
- 🛡️ Sensitive data protection

## Installation

Install the package via Composer:

```bash
composer require magiclog/request-logger
```

Run the migrations:

```bash
php artisan migrate
```

## Dashboard Access

Access the dashboard at `/request-logger` (requires authentication)

## Advanced Features

- Detailed request logging
- Performance tracking
- Error rate monitoring
- Exportable logs

## Performance Considerations

- Configurable log rotation
- Lightweight logging mechanism
- Minimal performance overhead

## Security

- Authentication required
- Sensitive data filtering
- IP and user tracking

## Configuration Options
- Add this REQUEST_LOGGER_AUTH_GUARD to your .env file to specify the guard to use for authentication to avoid the public access to the dashboard

## Troubleshooting

- Check configuration file
- Ensure proper middleware registration
- Verify database connection

## Contributing

Contributions are welcome! Please submit pull requests.

## Performance

This package is designed to be lightweight but we store the request data in the database so it can have an impact on the performance of your application. We recommend using this package for simple applications or simple api's.

## License

MIT License
