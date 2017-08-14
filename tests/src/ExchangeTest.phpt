<?php

namespace h4kuna\Exchange;

use h4kuna\Number,
	Tester\Assert,
	Nette\DI;

require_once __DIR__ . '/../bootstrap.php';

$compiler = new DI\Compiler();
$compiler->addConfig([
	'parameters' => [
		'tempDir' => TEMP_DIR
	],
	'services' => [
		'router' => \Nette\Application\Routers\SimpleRouter::class,
		'numberFormatFactory' => Number\NumberFormatFactory::class
	]
]);

$extension = new \h4kuna\Exchange\DI\ExchangeExtension();
$extension->setConfig([
	'vat' => 21,
	'defaultFormat' => ['decimals' => 3],
	'session' => TRUE
]);
$compiler->addExtension('exchange', $extension);
$compiler->addExtension('latte', new \Nette\Bridges\ApplicationDI\LatteExtension(TEMP_DIR));
$compiler->addExtension('application', new \Nette\Bridges\ApplicationDI\ApplicationExtension(FALSE, NULL, TEMP_DIR));
$compiler->addExtension('http', new \Nette\Bridges\HttpDI\HttpExtension);
$compiler->addExtension('session', new \Nette\Bridges\HttpDI\SessionExtension());

eval($compiler->compile());
$container = new \Container();

Assert::type(ExchangeManager::class, $container->getService('exchange.exchangeManager'));

Assert::type(Number\NumberFormatFactory::class, $container->getService('exchange.numberFormatFactory'));
Assert::same($container->getService('numberFormatFactory'), $container->getService('exchange.numberFormatFactory'));

Assert::type(Currency\Formats::class, $container->getService('exchange.formats'));

Assert::type(Caching\Cache::class, $container->getService('exchange.cache'));

Assert::type(Exchange::class, $container->getService('exchange.exchange'));

Assert::type(Number\Tax::class, $container->getService('exchange.vat'));

Assert::type(Filters::class, $container->getService('exchange.filters'));

Assert::type(\Nette\Bridges\ApplicationLatte\ILatteFactory::class, $container->getService('latte.latteFactory'));

Assert::type(\Nette\Application\Application::class, $container->getService('application.application'));