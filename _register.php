<?php

use Mmo\Instrumentation\Yii2\Yii2WebInstrumentation;
use OpenTelemetry\SDK\Sdk;

if (class_exists(Sdk::class) && Sdk::isInstrumentationDisabled(Yii2WebInstrumentation::NAME) === true) {
    return;
}

if (extension_loaded('opentelemetry') === false) {
    trigger_error('The opentelemetry extension must be loaded in order to autoload the Yii2 Web auto-instrumentation', E_USER_WARNING);

    return;
}

Yii2WebInstrumentation::register();
