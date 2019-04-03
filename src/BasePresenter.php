<?php

namespace Grapesc\GrapeFluid\Application;

use Grapesc\GrapeFluid\AssetRepository;
use Grapesc\GrapeFluid\AssetsControl\AssetsControl;
use Grapesc\GrapeFluid\BaseParametersRepository;
use Grapesc\GrapeFluid\CoreModule\Service\BreadCrumbService;
use Grapesc\GrapeFluid\CoreModule\Model\SettingModel;
use Grapesc\GrapeFluid\FluidTranslator;
use Grapesc\GrapeFluid\ImageStorage;
use Grapesc\GrapeFluid\LinkCollector\LinkCollector;
use Grapesc\GrapeFluid\MagicControl\Creator;
use Grapesc\GrapeFluid\MagicControl\MagicControl;
use Grapesc\GrapeFluid\ModuleRepository;
use Grapesc\GrapeFluid\ScriptCollector;
use Grapesc\GrapeFluid\Security\NamespacesRepository;
use Nette\Caching\Storages\FileStorage;
use Nette\Forms\Form;


abstract class BasePresenter extends \Nette\Application\UI\Presenter
{
	
	/** @var Creator @inject */
	public $magicControlCreator;

	/** @var AssetRepository @inject */
	public $assets;
	
	/** @var BaseParametersRepository @inject */
	public $appParams;

	/** @var FluidTranslator @inject */
	public $translator;

	/** @var FileStorage @inject */
	public $storage;

	/** @var SettingModel @inject */
	public $setting;

	/** @var LinkCollector @inject */
	public $collector;

	/** @var ScriptCollector @inject */
	public $scriptCollector;

	/** @var ImageStorage @inject */
	public $imageStorage;

	/** @var BreadCrumbService @inject */
	public $breadCrumbService;

	/** @var ActionTemplateOverloading @inject */
	public $actionTemplateOverloading;

	/** @var ModuleRepository @inject */
	public $moduleRepository;

	/** @var NamespacesRepository @inject */
	public $namespacesRepository;

	/** @var AssetsControl @inject */
	public $assetsControl;

	/**	@var bool @persistent */
	public $_noLayout = false;

	/** @var string */
	protected $defaultNamespace = 'frontend';


	public function formatLayoutTemplateFiles()
	{
		$list = parent::formatLayoutTemplateFiles();
		//todo zkontrolovat
//		$list[] = $this->appParams->getParam('libDir') . $this->layout . '.latte';
		return $list;
	}


	protected function startup()
	{
		parent::startup();
		$this->setLayout(__DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . '@layout.latte');
		$this->setSubLayout($this->context->getParameters()['project']['layout']);
		$this->setFlashMessagesFile($this->context->getParameters()['project']['flashes']);
		$this->setMeta([
			//cremeta foreachnout a nastavit
			"title"       => $this->setting->getVal("core.meta.title"),
			"keywords"    => $this->setting->getVal("core.meta.keywords"),
			"description" => $this->setting->getVal("core.meta.description"),
			"author"      => $this->setting->getVal("core.meta.author")
		]);

		$this->actionTemplateOverloading->override($this);
		$this->namespacesRepository->setCurrentNamespace($this->defaultNamespace);

		$this->breadCrumbService->addLink($this->translator->translate('Úvod'), rtrim($this->getHttpRequest()->getUrl()->getBaseUrl(), '/'));
	}


	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->_noLayout = $this->_noLayout;
	}


	protected function createComponent($name)
	{
		if (substr($name, 0, 3) == 'mc_') {
			$exploded = explode("_", $name);

			unset($exploded[sizeof($exploded) - 1]);
			unset($exploded[0]);
			$magicControl = implode("_", $exploded);

			if ($this->magicControlCreator->magicControlExist($magicControl)) {
				return new MagicControl(substr($name, strlen($magicControl) + 4), $this->magicControlCreator, $magicControl);
			}
		}

		$component = parent::createComponent($name);
		if ($component instanceof Form) {
			$component->setTranslator($this->translator);
		}

		return $component;
	}


	protected function createTemplate($class = NULL)
	{
		/** @var \Nette\Bridges\ApplicationLatte\Template $template */
		$template = parent::createTemplate();
		$template->setTranslator($this->translator);
		return $template;
	}


	public function createComponentAssets()
	{
		return $this->assetsControl;
	}


	/**
	 * Vrátí jméno aktuálního modulu podle aktuálního namespace presenteru
	 *
	 * @return mixed
	 */
	public function getModuleName()
	{
		return str_replace("Module", "", explode("\\", get_class($this))[2]);
	}


	/**
	 * Možnost nastavit meta tagy webu ve formátu:
	 * [ $argumentName => 'value' ]
	 *
	 * @param array $meta
	 * @param string $argumentName
	 */
	public function setMeta(array $meta, $argumentName = 'name')
	{
		/** @var \Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->template;

		if (!isset($template->meta)) {
			$template->meta = [];
		}

		if (!key_exists($argumentName, $template->meta)) {
			$template->meta[$argumentName] = [];
		}

		foreach ($meta as $var => $val) {
			$template->meta[$argumentName][$var] = $val;
		}
	}


	/**
	 * @param string $path
	 */
	protected function setSubLayout($path)
	{
		if (!$this->_noLayout) {
			$this->template->sublayout = $path;
		}
	}


	/**
	 * @param string $path
	 * @return void
	 */
	protected function setFlashMessagesFile($path)
	{
		$this->template->flashMessagesFile = $path;
	}


	/**
	 * Flash zprava s podporou translatoru
	 *
	 * @param $message
	 * @param string $type
	 * @return \stdClass
	 */
	public function flashMessage($message, $type = 'info')
	{
		return parent::flashMessage($this->translator->translate($message), $type);
	}


	/**
	 * @param string $moduleName
	 * @return bool
	 */
	public function ifModuleExists($moduleName)
	{
		return $this->moduleRepository->moduleExist($moduleName);
	}

}
