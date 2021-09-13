<?php

$container = new Pimple\Container();

$container['db.hostname'] = 'localhost';
$container['db.database'] = 'lss2';
$container['db.username'] = 'linkshare';
$container['db.password'] = 'linkshare';
$container['db.handle'] = function () use ($container) {
    $hostname = $container['db.hostname'];
    $username = $container['db.username'];
    $password = $container['db.password'];
    $database = $container['db.database'];
    $handle = new PDO('mysql:host=' . $hostname . ';dbname=' . $database, $username, $password);
    $handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $handle->query('set names utf8');
    // Required to make VascodaExporter::collectAdminData work.
    $handle->query('set sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))');
    return $handle;
};

$container['ListIdentifiers'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $command = new SubHH\Linkshare\OAI\ListIdentifiers($mapper);
    return $command;
};

$container['ListSets'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $command = new SubHH\Linkshare\OAI\ListSets($mapper);
    return $command;
};

$container['ListMetadataFormats'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $command = new SubHH\Linkshare\OAI\ListMetadataFormats($mapper);
    return $command;
};

$container['Identify'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $command = new SubHH\Linkshare\OAI\Identify($mapper);
    return $command;
};

$container['GetRecord'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $mapper->addSerializer('oai_dc', new SubHH\Linkshare\OAI\DublinCore($handle));
    $mapper->addSerializer('vascoda', new SubHH\Linkshare\OAI\Legacy\VascodaExporter($handle));
    $command = new SubHH\Linkshare\OAI\GetRecord($mapper);
    return $command;
};

$container['ListRecords'] = function () use ($container) {
    $handle = $container['db.handle'];
    $mapper = new SubHH\Linkshare\OAI\Mapper($handle);
    $mapper->addSerializer('oai_dc', new SubHH\Linkshare\OAI\DublinCore($handle));
    $mapper->addSerializer('vascoda', new SubHH\Linkshare\OAI\Legacy\VascodaExporter($handle));
    $command = new SubHH\Linkshare\OAI\ListRecords($mapper);
    return $command;
};
