<?php

namespace h4kuna\Exchange\DI;

use h4kuna\Exchange,
	h4kuna\Number,
	Nette\DI as NDI;

final class ExchangeExtension extends NDI\CompilerExtension
{

	private $defaults = [
		'vat' => null, // 21
		'strict' => true, // download only defined currencies
		'defaultFormat' => [], // default number format
		'currencies' => [
			'czk' => ['unit' => 'Kč'],
			'eur' => ['unit' => '€', 'mask' => 'U 1'],
			'usd' => ['unit' => '$', 'mask' => 'U1']
		],
		'tempDir' => '', // cache
		'session' => false, // true or false
		'managerParameter' => 'currency', // parameter for query, cookie and session
		'filters' => [
			'currency' => 'currency',
			'vat' => 'vat'
		]
	];


	public function __construct($tempDir = null)
	{
		$this->defaults['tempDir'] = $tempDir . DIRECTORY_SEPARATOR . 'currencies';
	}


	public function loadConfiguration()
	{
		$config = $this->config + $this->defaults;
		$builder = $this->getContainerBuilder();

		// ExchangeManager
		$exchangeManager = $builder->addDefinition($this->prefix('exchangeManager'))
			->setClass(Exchange\ExchangeManager::class)
			->addSetup('setParameter', [$config['managerParameter']])
			->setAutowired(false);

		if ($config['session']) {
			$exchangeManager->addSetup('setSession', [new NDI\Statement('?->getSection(\'h4kuna.exchange\')', ['@session.session'])]);
		}

		// number format factory
		$nff = $builder->addDefinition($this->prefix('numberFormatFactory'))
			->setClass(Number\NumberFormatFactory::class)
			->setAutowired(false);

		// formats
		$formats = $builder->addDefinition($this->prefix('formats'))
			->setClass(Exchange\Currency\Formats::class, [$nff])
			->setAutowired(false);

		if ($config['defaultFormat']) {
			$formats->addSetup('setDefaultFormat', [$config['defaultFormat']]);
		}

		$allowedCurrencies = [];
		foreach ($config['currencies'] as $code => $setup) {
			$code = strtoupper($code);
			$allowedCurrencies[] = $code;
			if ($setup) {
				$formats->addSetup('addFormat', [$code, $setup]);
			}
		}

		// cache
		$cache = $builder->addDefinition($this->prefix('cache'))
			->setClass(Exchange\Caching\Cache::class, [$config['tempDir']])
			->setAutowired(false);

		if ($config['strict']) {
			$cache->addSetup('setAllowedCurrencies', [$allowedCurrencies]);
		}

		// exchange
		$exchange = $builder->addDefinition($this->prefix('exchange'))
			->setClass(Exchange\Exchange::class, [$cache]);

		// filters
		$filters = $builder->addDefinition($this->prefix('filters'))
			->setClass(Exchange\Filters::class, [$exchange, $formats])
			->setAutowired(false);

		if ($config['vat']) {
			$vat = $builder->addDefinition($this->prefix('vat'))
				->setClass(Number\Tax::class, [$config['vat']]);
			$filters->addSetup('setVat', [$vat]);
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		$definitions = $builder->findByType(Number\NumberFormatFactory::class);
		unset($definitions[$this->prefix('numberFormatFactory')]);
		if ($definitions) {
			$definition = key($definitions);
			$builder->getDefinition($this->prefix('numberFormatFactory'))
				->setFactory('@' . $definition);
		}

		if ($builder->hasDefinition('application.application')) {
			$application = $builder->getDefinition('application.application');
			$application->addSetup(new NDI\Statement('$service->onPresenter[] = function($application, $presenter) {?->init($presenter);}', [$this->prefix('@exchangeManager')]));
		}

		if ($builder->hasDefinition('latte.latteFactory')) {
			$latte = $builder->getDefinition('latte.latteFactory')
				->addSetup('addFilter', [$this->config['filters']['currency'], [$this->prefix('@filters'), 'format']]);
			if ($this->config['vat']) {
				$latte->addSetup('addFilter', [
					$this->config['filters']['vat'],
					[$this->prefix('@filters'), 'formatVat']
				]);
			}
		}
	}

}
