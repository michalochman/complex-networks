<?php

require_once __DIR__ . '/vendor/simplehtmldom_1_5/simple_html_dom.php';
require_once __DIR__ . '/vendor/sfWebBrowserPlugin-1.1.2/lib/sfWebBrowser.class.php';
require_once __DIR__ . '/vendor/sfWebBrowserPlugin-1.1.2/lib/sfCurlAdapter.class.php';

class Crawler extends sfWebBrowser
{
	protected $responseSimpleDom;

	/**
	 * Get a simple_html_dom version of the response
	 *
	 * @return simple_html_dom The reponse contents
	 */
	public function getResponseSimpleDom()
	{
		if(!$this->responseSimpleDom)
		{
			// for HTML/XML content, create a DOM object for the response content
			if (preg_match('/(x|ht)ml/i', $this->getResponseHeader('Content-Type')))
			{
				$this->responseSimpleDom = new simple_html_dom();
				@$this->responseSimpleDom->load($this->getResponseText());
			}
		}

		return $this->responseSimpleDom;
	}

	/**
	 * Initializes the response and erases all content from prior requests
	 */
	public function initializeResponse()
	{
		$this->responseSimpleDom = null;
		parent::initializeResponse();
	}
}
