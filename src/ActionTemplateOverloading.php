<?php

namespace Grapesc\GrapeFluid\Application;

use Nette\Application\UI\Presenter;

class ActionTemplateOverloading
{

	/** @var array */
	private $setting;


	public function __construct(array $setting)
	{
		$this->setting = $setting;
	}


	/**
	 * @param Presenter $presenter
	 */
	public function override(Presenter $presenter)
	{
		$action = $presenter->getAction(TRUE);
		if (isset($this->setting[$action])) {
			$presenter->getTemplate()->setFile($this->setting[$action]);
		}
	}

}
