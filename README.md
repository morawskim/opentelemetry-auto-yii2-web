# OpenTelemetry Auto-Instrumentation for Yii2 Web

This package provides automatic instrumentation for the [Yii2](https://www.yiiframework.com/) web applications using [OpenTelemetry](https://opentelemetry.io/).

## Requirements

-   PHP 8.2+
-   OpenTelemetry PHP Extension (`ext-opentelemetry`)

## Installation

You can install the package via [Composer](https://getcomposer.org/):

```bash
composer require mmo/opentelemetry-auto-yii2-web
```

## Usage

The instrumentation is automatically registered via Composer's autoloading mechanism (using `_register.php`). As long as the package is installed and the `opentelemetry` extension is loaded, it will hook into Yii2's core methods.

### Disabling Instrumentation

You can disable this specific instrumentation by setting the `OTEL_PHP_DISABLED_INSTRUMENTATIONS` environment variable:

```bash
export OTEL_PHP_DISABLED_INSTRUMENTATIONS=mmo-yii2-web
```

## How it works

The library uses the OpenTelemetry `hook()` function to intercept key Yii2 methods:

-   **Inline Actions**: Wraps `runWithParams` in a span to monitor action logic execution.
-   **View Rendering**: Wraps `render` in a span to measure time spent rendering templates.
-   **View File Path**: Hooks into `findViewFile` to enrich the current span with the exact path of the rendered file.

## License

This project is licensed under the MIT License. See the [LICENSE.txt](LICENSE.txt) file for details.
