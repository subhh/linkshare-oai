<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../config/service.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$request = Request::createFromGlobals();

switch ($request->getMethod()) {
case 'GET':
case 'HEAD':
    $parameters = new HAB\OAI\PMH\Request\Parameters($request->query->all());
    break;
case 'POST':
    $parameters = new HAB\OAI\PMH\Request\Parameters($request->request->all());
    break;
default:
    $response = new Response(Response::$statusTexts[405], 405, ['Allow' => 'GET, HEAD, POST']);
    $response->prepare($request);
    $response->send();
    exit;
}

$psr11 = new Pimple\Psr11\Container($container);
$commands = new HAB\OAI\PMH\Command\CommandFactory($psr11);
$controller = new HAB\OAI\PMH\Request\Controller($commands);

try {
    $payload = $controller->handle('https://linkshare.sub.uni-hamburg.de/oai', $parameters);

    $writer = new HAB\OAI\PMH\Response\Writer(true);
    $body = $writer->serialize($payload);

    $response = new Response($body, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    $response->prepare($request);
    $response->send();
    exit;
} catch (Throwable $e) {
    $response = new Response(Response::$statusTexts[500], 500);
    $response->prepare($request);
    $response->send();
    exit;
}
