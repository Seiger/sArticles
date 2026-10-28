<?php
/** Isolated CMS container for API event regression tests. */
function evo() { return $GLOBALS['apiTestApp']; }
require __DIR__ . '/bootstrap.php';
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
/** Supply only the CMS configuration and access hooks used by this test. */
$app = new class extends Container {
    public int $documentIdentifier = 1;
    public int $denials = 0;
    /** Return isolated configuration without bootstrapping a site. */
    public function getConfig($name, $default = null) { return $default; }
    /** Record an access denial without emitting a response. */
    public function sendErrorPage(): void { $this->denials++; }
};
$GLOBALS['apiTestApp'] = $app;
$events = new Dispatcher($app);
$app->instance('events', $events);
Facade::setFacadeApplication($app);
if (!class_exists('Event', false)) { class_alias(Illuminate\Support\Facades\Event::class, 'Event'); }
require dirname(__DIR__, 2) . '/plugins/sArticlesPlugin.php';
$events->dispatch('evolution.OnPageNotFound', [[]]);
if ($app->bound('request') || $app->denials !== 0) { throw new RuntimeException('API event must not bind a request or deny access'); }
echo "API request guard passed\n";
