<?php

namespace h4kuna\Exchange\DI;

use h4kuna\Exchange,
	h4kuna\Number,
	Nette\DI as NDI;

final class ExchangeExtension extends NDI\CompilerExtension
{

	private $defaults = [
		'vat' => NULL, // 21
		'strict' => TRUE, // download only defined currencies
		'defaultFormat' => [], // default number format
		'currencies' => [
			'czk' => ['unit' => 'Kč'],
			'eur' => ['unit' => '€', 'mask' => 'U 1'],
			'usd' => ['unit' => '$', 'mask' => 'U1']
		],
		'tempDir' => '%tempDir%/currencies', // cache
		'session' => FALSE, // TRUE or FALSE
		'managerParameter' => 'currency', // parameter for query, cookie and session
		'filters' => [
			'currency' => 'currency',
			'vat' => 'vat'
		]
	];

	public function loadConfiguration()
	{
		$this->config += $this->defaults;
		$builder = $this->getContainerBuilder();
		$config = NDI\Helpers::expand($this->config, $builder->parameters);

		// ExchangeManager
		$exchangeManager = $builder->addDefinition($this->prefix('exchangeManager'))
			->setClass(Exchange\ExchangeManager::class)
			->addSetup('setParameter', [$config['managerParameter']])
			->setAutowired(FALSE);

		if ($config['session']) {
			$exchangeManager->addSetup('setSession', [new NDI\Statement('?->getSection(\'h4kuna.exchange\')', ['@session.session'])]);
		}

		// number format factory
		$nff = $builder->addDefinition($this->prefix('numberFormatFactory'))
			->setClass(Number\NumberFormatFactory::class);

		// formats
		$formats = $builder->addDefinition($this->prefix('formats'))
			->setClass(Exchange\Currency\Formats::class, [$nff])
			->setAutowired(FALSE);

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
			->setAutowired(FALSE);

		if ($config['strict']) {
			$cache->addSetup('setAllowedCurrencies', [$allowedCurrencies]);
		}

		// exchange
		$exchange = $builder->addDefinition($this->prefix('exchange'))
			->setClass(Exchange\Exchange::class, [$cache]);

		// filters
		$filters = $builder->addDefinition($this->prefix('filters'))
			->setClass(Exchange\Filters::class, [$exchange, $formats])
			->setAutowired(FALSE);

		if ($config['vat']) {
			$vat = $builder->addDefinition($this->prefix('vat'))
				->setClass(Number\Tax::class, [$config['vat']]);
			$filters->addSetup('setVat', [$vat]);
		}
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		if ($builder->hasDefinition('application.application')) {
			$application = $builder->getDefinition('application.application');
			$application->addSetup(new NDI\Statement('$service->onPresenter[] = function() {?->init();}', [$this->prefix('@exchangeManager')]));
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
