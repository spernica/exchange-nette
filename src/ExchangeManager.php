<?php

namespace h4kuna\Exchange;

use Nette,
	Nette\Http;

class ExchangeManager
{
	use Nette\SmartObject;

	/** @var callable[] */
	public $onChangeCurrency;

	/** @var Exchange */
	private $exchange;

	/** @var Http\Request */
	protected $request;

	/** @var Http\Response */
	protected $response;

	/** @var Http\SessionSection */
	protected $session;

	protected $parameter = 'currency';

	public function __construct(Exchange $exchange, Http\Request $request, Http\Response $response)
	{
		$this->exchange = $exchange;
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * @param Http\SessionSection $session
	 */
	public function setSession(Http\SessionSection $session)
	{
		$this->session = $session;
	}

	/**
	 * @param string $parameter
	 */
	public function setParameter($parameter)
	{
		$this->parameter = $parameter;
	}

	public function init(Nette\Application\IPresenter $presenter)
	{
		$value = $this->setCurrency($this->getQuery());
		if ($value === NULL) {
			$value = $this->initCookie();
			if ($value === NULL) {
				$this->initSession();
			}
		} else {
			$this->onChangeCurrency($presenter, $value, $this->exchange);
		}
	}

	public function setCurrency($code)
	{
		if ($code === NULL) {
			return NULL;
		}
		try {
			$value = $this->exchange->setOutput($code)->code;
		} catch (UnknownCurrencyException $e) {
			return NULL;
		}

		$this->saveCookie($value);
		if ($this->session !== NULL) {
			$this->saveSession($value);
		}

		return $value;
	}

	private function initCookie()
	{
		$value = $this->setCurrency($this->getCookie());
		if ($value === NULL) {
			$this->deleteCookie();
		}
		return $value;
	}

	private function initSession()
	{
		if ($this->session === NULL) {
			return NULL;
		}
		return $this->setCurrency($this->getSession());
	}

	protected function getSession()
	{
		return $this->session->{$this->parameter};
	}

	protected function getQuery()
	{
		return $this->request->getQuery($this->parameter);
	}

	protected function getCookie()
	{
		return $this->request->getCookie($this->parameter);
	}

	protected function saveCookie($code)
	{
		$this->response->setCookie($this->parameter, $code, '+6 month');
	}

	protected function saveSession($code)
	{
		$this->session->{$this->parameter} = $code;
		$this->session->setExpiration('+7 days');
	}

	protected function deleteCookie()
	{
		$this->response->deleteCookie($this->parameter);
	}

}
