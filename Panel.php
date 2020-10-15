<?php

/**
 * @author Daniel Robenek
 * @author Jan Langer, improvements, update to latest Nette
 * @license MIT
 */

namespace PresenterLink;

use Nette;
use Nette\Application\UI\ITemplate;
use Tracy\Debugger;
use Tracy\IBarPanel;

class Panel implements IBarPanel
{

	const ACTIVE = 1;
	const PARENTS = 2;
	const BOTH = 3;

	/** @var Nette\Application\Application */
	private $application;

	/** @var string */
	private $appDir;

	public function __construct($appDir, Nette\Application\Application $application)
	{
		$this->application = $application;
		$this->appDir = $appDir;
	}

	/**
	 * @return Nette\Application\UI\Presenter
	 */
	private function getPresenter()
	{
		return $this->application->getPresenter();
	}

	public function getTab()
	{
		return
			'<span><svg x="0px" y="0px" width="405.24px" height="405.24px" viewBox="0 0 405.24 405.24" style="" xml:space="preserve">'
			. '<path fill="#336699" d="M249.037,330.626H28.283V86.909h335.623v181.629l28.283,28.283V26.195c0-12.996-10.573-23.569-23.568-23.569H23.568 C10.573,2.626,0,13.199,0,26.195v309.146c0,12.995,10.573,23.568,23.568,23.568h238.911 C249.37,340.274,249.037,330.626,249.037,330.626z M338.026,42.202c0-4.806,3.896-8.702,8.702-8.702h8.701 c4.807,0,8.702,3.896,8.702,8.702v9.863c0,4.806-3.896,8.702-8.702,8.702h-8.701c-4.808,0-8.702-3.896-8.702-8.702V42.202z M297.561,42.202c0-4.806,3.896-8.702,8.701-8.702h8.703c4.808,0,8.702,3.896,8.702,8.702v9.863c0,4.806-3.896,8.702-8.702,8.702 h-8.703c-4.806,0-8.701-3.896-8.701-8.702V42.202z M257.095,42.202c0-4.806,3.897-8.702,8.702-8.702h8.702 c4.807,0,8.703,3.896,8.703,8.702v9.863c0,4.806-3.896,8.702-8.703,8.702h-8.702c-4.805,0-8.702-3.896-8.702-8.702V42.202z"/>'
			. '<path fill="#009933" d="M392.606,322.19l-41.165-41.166c-9.292-9.291-21.797-13.446-33.972-12.487c0.958-12.175-3.196-24.679-12.487-33.972 l-41.165-41.165c-16.848-16.846-44.256-16.845-61.103,0l-6.689,6.688c-16.846,16.845-16.846,44.255,0,61.102l41.166,41.164 c9.293,9.293,21.797,13.446,33.971,12.489c-0.958,12.174,3.197,24.679,12.489,33.972l41.165,41.164 c16.845,16.846,44.255,16.846,61.101,0l6.688-6.688C409.452,366.445,409.452,339.035,392.606,322.19z M262.267,274.187 l17.062,17.063c-8.687,5.173-20.118,4.027-27.586-3.439l-41.166-41.166c-8.824-8.822-8.824-23.182,0-32.006l6.688-6.688c8.823-8.824,23.182-8.824,32.004,0l41.166,41.165c7.47,7.469,8.613,18.898,3.439,27.587l-17.062-17.063 c-4.019-4.018-10.53-4.018-14.548,0C258.248,263.656,258.248,270.169,262.267,274.187z M378.058,368.743l-6.688,6.688 c-8.824,8.824-23.181,8.824-32.005,0.001L298.2,334.267c-7.468-7.469-8.614-18.898-3.44-27.586l17.063,17.062 c4.018,4.019,10.529,4.019,14.548,0c4.017-4.018,4.017-10.53,0-14.548l-17.063-17.063c8.688-5.174,20.118-4.027,27.589,3.44 l41.164,41.165C386.882,345.562,386.882,359.919,378.058,368.743z"/>'
			. '</svg></span>';
	}

	public function getPanel()
	{
		if (!$this->getPresenter()) {
			return "";
		}

		$componentMethods = $this->getComponentMethods();

		$parameters = [
			'presenterClass' => $this->getPresenter()->getReflection(),
			'actionName' => $this->getPresenter()->getAction(TRUE),
			'templateFileName' => $this->getTemplateFileName(),
			'layoutFileName' => $this->getLayoutFileName(),
			'appDirPathLength' => strlen(realpath($this->appDir)),
			'interestedMethods' => $this->getInterestedMethodReflections(),
			'parentClasses' => $this->getParentClasses(),
			'usedComponentMethods' => $this->getUsedComponentMethods($componentMethods),
			'unusedComponentMethods' => $this->getUnusedComponentMethods($componentMethods),
			'editorLink' => function ($file, $line = 1) {
				return self::getEditorLink($file, $line);
			}
		];

		$template = function () use ($parameters) {
			extract($parameters);

			ob_start();
			require_once __DIR__ . '/template/template.phtml';

			return ob_get_clean();
		};

		return $template();
	}

	protected function getInterestedMethodNames()
	{
		return array(
			"startup" => self::BOTH,
			$this->getActionMethodName() => self::BOTH,
			$this->getRenderMethodName() => self::BOTH,
			"beforeRender" => self::BOTH,
			"afterRender" => self::BOTH,
			"shutdown" => self::BOTH,
			"formatLayoutTemplateFiles" => self::BOTH,
			"formatTemplateFiles" => self::BOTH,
		);
	}

	private function getTemplateFileName()
	{
		$template = $this->getPresenter()->getTemplate();
		$templateFile = $template->getFile();
		$isDeprecatedFileTemplate = interface_exists('Nette\Templating\IFileTemplate') && $template instanceof Nette\Templating\IFileTemplate;
		if (($template instanceof ITemplate || $isDeprecatedFileTemplate) && !$template->getFile()) {
			$files = $this->getPresenter()->formatTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$templateFile = $file;
					break;
				}
			}
			if (!$templateFile) {
				$templateFile = str_replace($this->appDir, "\xE2\x80\xA6", reset($files));
			}
		}
		if ($templateFile !== NULL) {
			$templateFile = realpath($templateFile);
		}

		return $templateFile;
	}

	private function getLayoutFileName()
	{
		$layoutFile = $this->getPresenter()->getLayout();
		if ($layoutFile === NULL) {
			$files = $this->getPresenter()->formatLayoutTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$layoutFile = $file;
					break;
				}
			}
			if (!$layoutFile) {
				$layoutFile = str_replace($this->appDir, "\xE2\x80\xA6", reset($files));
			}
		}
		if ($layoutFile !== NULL) {
			$layoutFile = realpath($layoutFile);
		}

		return $layoutFile;
	}

	private function getActionMethodName()
	{
		return "action" . ucfirst($this->getPresenter()->getAction(FALSE));
	}

	private function getRenderMethodName()
	{
		return "render" . ucfirst($this->getPresenter()->getAction(FALSE));
	}

	private function getInterestedMethodReflections()
	{
		$interestedMethods = $this->getInterestedMethodNames();
		$cr = $this->getPresenter()->getReflection();
		$methods = array();
		foreach ($interestedMethods as $methodName => $scope) {
			if ($scope & self::ACTIVE && $cr->hasMethod($methodName)) {
				$method = $cr->getMethod($methodName);
				if ($method->getDeclaringClass()->getName() == $cr->getName()) {
					$methods[] = $method;
				}
			}
		}

		return $methods;
	}

	private function getParentClasses()
	{
		$interestedMethods = $this->getInterestedMethodNames();
		$parents = array();
		$cr = $this->getPresenter()->getReflection()->getParentClass();
		while ($cr !== NULL && $cr->getName() != "Presenter" && $cr->getName() != "Nette\\Application\\UI\\Presenter") {
			$methods = array();
			foreach ($interestedMethods as $methodName => $scope) {
				if ($scope & self::PARENTS && $cr->hasMethod($methodName)) {
					$method = $cr->getMethod($methodName);
					if ($method->getDeclaringClass()->getName() == $cr->getName()) {
						$methods[] = $method;
					}
				}
			}
			$parents[] = array(
				"reflection" => $cr,
				"methods" => $methods,
			);
			$cr = $cr->getParentClass();
		}

		return $parents;
	}

	private function getUsedComponentMethods($componentMethods)
	{
		return array_filter(
			$componentMethods,
			function ($var) {
				return $var['isUsed'];
			}
		);
	}

	private function getUnusedComponentMethods($componentMethods)
	{
		return array_filter(
			$componentMethods,
			function ($var) {
				return !$var['isUsed'];
			}
		);
	}

	private function getComponentMethods()
	{
		$components = (array)$this->getPresenter()->getComponents(FALSE);
		$methods = $this->getPresenter()->getReflection()->getMethods();
		$result = array();
		foreach ($methods as $method) {
			if (strpos($method->getName(), "createComponent") === 0 && strlen($method->getName()) > 15) {
				$componentName = substr($method->getName(), 15);
				$componentName{0} = strtolower($componentName{0});
				$isUsed = isset($components[$componentName]);
				$result[] = array("method" => $method, "isUsed" => $isUsed);
			}
		}

		return $result;
	}

	public static function getEditorLink($file, $line = 1)
	{
		if ($file instanceof \ReflectionMethod || $file instanceof \ReflectionClass) {
			$line = $file->getStartLine();
			$file = $file->getFileName();
		}
		$line = (int)$line;

		return strtr(Debugger::$editor, array('%file' => $file, '%line' => $line));
	}

}
