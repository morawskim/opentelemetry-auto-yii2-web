<?php

declare(strict_types=1);

namespace Mmo\Instrumentation\Yii2;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\Context\Context;
use yii\base\InlineAction;
use yii\base\View;
use function OpenTelemetry\Instrumentation\hook;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Version;
use Throwable;

class Yii2WebInstrumentation
{
    public const NAME = 'mmo-yii2-web';

    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(
            'mmo.yii2.web',
            null,
            Version::VERSION_1_36_0->url(),
        );

        hook(
            InlineAction::class,
            'runWithParams',
            pre: static function (
                InlineAction $action,
                array $params,
                string $class,
                string $function,
                ?string $filename,
                ?int $lineno,
            ) use ($instrumentation) {
                $builder = self::makeBuilder($instrumentation, 'InlineAction::runWithParams', $function, $class, $filename, $lineno);
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: static function (InlineAction $action, array $params, mixed $returnValue, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }
                $span = Span::fromContext($scope->context());
                self::end($exception);
            }
        );

        hook(
            View::class,
            'render',
            pre: static function (
                View $view,
                array $params,
                string $class,
                string $function,
                ?string $filename,
                ?int $lineno,
            ) use ($instrumentation) {
                $builder = self::makeBuilder($instrumentation, 'View::render', $function, $class, $filename, $lineno);
                $parent = Context::getCurrent();
                $span = $builder->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
            },
            post: static function (View $view, array $params, mixed $returnValue, ?Throwable $exception) {
                $scope = Context::storage()->scope();
                if (!$scope) {
                    return;
                }
                $span = Span::fromContext($scope->context());
                self::end($exception);
            }
        );
        hook(
            View::class,
            'findViewFile',
            post: static function (View $view, array $params, string $returnValue, ?Throwable $exception) {
                if (null === $exception) {
                    $span = Span::getCurrent();
                    $span->setAttribute('yii2.view_path', $returnValue);
                }
            },
        );
    }
    private static function makeBuilder(
        CachedInstrumentation $instrumentation,
        string $name,
        string $function,
        string $class,
        ?string $filename,
        ?int $lineno
    ): SpanBuilderInterface {
        return $instrumentation->tracer()
            ->spanBuilder($name)
            ->setAttribute(CodeAttributes::CODE_FUNCTION_NAME, sprintf('%s::%s', $class, $function))
            ->setAttribute(CodeAttributes::CODE_FILE_PATH, $filename)
            ->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $lineno);
    }
    private static function end(?Throwable $exception): void
    {
        $scope = Context::storage()->scope();
        if (!$scope) {
            return;
        }
        $scope->detach();
        $span = Span::fromContext($scope->context());
        if ($exception) {
            $span->recordException($exception);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $exception->getMessage());
        }

        $span->end();
    }
}
