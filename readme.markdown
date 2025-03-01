# Laravel Request Logger

A lightweight, feature-rich HTTP request logging and dashboard package for Laravel.

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

## License

MIT License
