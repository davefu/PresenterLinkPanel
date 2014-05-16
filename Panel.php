<?php

/**
 * @author Daniel Robenek
 * @author Jan Langer, improvements, update to latest Nette
 * @license MIT
 */

namespace PresenterLink;

use Nette\Diagnostics;
use Latte\Engine;
use Nette\Reflection;
use Nette\Templating;
use Nette;
use Nette\Utils\Html;

if (!class_exists('Latte\Engine')) {
    class_alias('Nette\Latte\Engine', 'Latte\Engine');
}

class Panel extends Nette\Object implements Diagnostics\IBarPanel {

    const ACTIVE = 1;
    const PARENTS = 2;
    const BOTH = 3;
    /** @var Nette\Application\Application */
    private $application;
    /** @var \Nette\Latte\Engine */
    private $latte;
    /** @var string */
    private $appDir;

    public function __construct($appDir, Nette\Application\Application $application, Engine $latte) {
        $this->application = $application;
        $this->latte = $latte;
        $this->appDir = $appDir;
    }

    /**
     * @return Nette\Application\UI\Presenter
     */
    private function getPresenter() {
        return $this->application->getPresenter();
    }

    public function getTab() {
        return Html::el("span")
                   ->add(
                   Html::el("img")
                       ->src("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAL+SURBVBgZBcFNaJtlAMDx//ORjzZbs7TJkmowbJcdZqr1oNavCiIIMraBh0IY7uZx8+OiVw9SQZgXp3gR3A5OtIigcxMcylyqVPADh0WNpO2bpk2bvm3e5P163sffT1hrATj/2drDwKXjR7JzwyhhGCVEScIoTlzgAOgBBugDO8DHwA0NAJDE8SMPVA7NvTpfAgAAwAuT/DBM8n3fVMMIDgLDf70BX//jPQtc1AAASRyXJ9ICgLU9Q0oItAClIZOS3JeRKClJKZitjnFPPjf54U/OOxIAwETRRE5DnMBBKHAj2AvA9cH1YWcEWwMDwOtX28wdy3F/MVXSAAAmiiYPpyVeAJ5vkFKgAaVAKlAIlIAEEGaf5r99fmm7jgYAMGFYzo8p3FHMMLBIaVESpBEoCQqLUoBVdPcD3r359z5wXgMAxGFYK0+kcH1LDGBBGYG0gAGFRVtJYsGkDHEYH/vi5cd3JQCACYNaJZ/BCy1CghICCUhAAADCgrUQBwEmDAyABnjuzetjWsl0JiUJjUFiAYsFDAIAAUgJkTEMvGEM7ANogDgIS7lcFinAD3xav/2Iu/4npakCTneHk0+d4dDhSW5f/4jfiwUek1uy67Rfm59/6z0NYMJgXOfSWBOxfONT8tLjxXMNPM9jfX2dZvMrVCrL2dOn0FrR6XTkysrK2+12uySeuHClCFw+Mz/7wvHsFs3vv2WhscDVT77kr1/vMF2pUK/X6XQ69Ho9OpubpI9Ut155qXF0aWnJ1SYMnwGeX7nb4k77Z2aq4wD0y6cYDG+xsLBAoVBgMBiwvb3N5fc/YHf8wW+Ac/l8PqNNFD10+umZsTcaj3Ltmkez2QSgtvs5a9KyuLhILpcDwPM8bJIwtXv7STjJxsaGr00UtTZ7Lldu3iXU0/TdAT98d4v6zAz1ep1ut8vq6iqZTIZarUa5XMYPo6PLy8t7juNsitnGpSJwEahhk6KK9qpToz9O3Fsp6kw6LYSA1qhEdnyCaVpYm9go8H3Hcbqe5539H/YvZvvl5HpaAAAAAElFTkSuQmCC")
            );
    }


    public function getPanel() {
        $template = new Templating\FileTemplate(dirname(__FILE__) . '/template/template.latte');
        $template->registerFilter($this->latte);
        $template->registerHelper("editorLink", callback(__CLASS__, "getEditorLink"));
        $template->registerHelper("substr", "substr");
        $template->registerHelperLoader('Nette\\Templating\\Helpers::loader');

        $template->presenterClass = $this->getPresenter()->getReflection();
        $template->actionName = $this->getPresenter()->getAction(TRUE);
        $template->templateFileName = $this->getTemplateFileName();
        $template->layoutFileName = $this->getLayoutFileName();
        $template->appDirPathLength = strlen(realpath($this->appDir));


        $template->interestedMethods = $this->getInterestedMethodReflections();

        $template->parentClasses = $this->getParentClasses();

        $componentMethods = $this->getComponentMethods();
        $template->usedComponentMethods = $this->getUsedComponentMethods($componentMethods);
        $template->unusedComponentMethods = $this->getUnusedComponentMethods($componentMethods);

        return $template->__toString();
    }

    protected function getInterestedMethodNames() {
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

    private function getTemplateFileName() {
        $template = $this->getPresenter()->getTemplate();
        $templateFile = $template->getFile();
        if ($template instanceof Templating\IFileTemplate && !$template->getFile()) {
            $files = $this->getPresenter()->formatTemplateFiles();
            foreach ($files as $file) {
                if (is_file($file)) {
                    $templateFile = $file;
                    break;
                }
            }
            if (!$templateFile)
                $templateFile = str_replace($this->appDir, "\xE2\x80\xA6", reset($files));
        }
        if ($templateFile !== NULL)
            $templateFile = realpath($templateFile);

        return $templateFile;
    }

    private function getLayoutFileName() {
        $layoutFile = $this->getPresenter()->getLayout();
        if ($layoutFile === NULL) {
            $files = $this->getPresenter()->formatLayoutTemplateFiles();
            foreach ($files as $file) {
                if (is_file($file)) {
                    $layoutFile = $file;
                    break;
                }
            }
            if (!$layoutFile)
                $layoutFile = str_replace($this->appDir, "\xE2\x80\xA6", reset($files));
        }
        if ($layoutFile !== NULL)
            $layoutFile = realpath($layoutFile);

        return $layoutFile;
    }

    private function getActionMethodName() {
        return "action" . ucfirst($this->getPresenter()->getAction(FALSE));
    }

    private function getRenderMethodName() {
        return "render" . ucfirst($this->getPresenter()->getAction(FALSE));
    }

    private function getInterestedMethodReflections() {
        $interestedMethods = $this->getInterestedMethodNames();
        $cr = $this->getPresenter()->getReflection();
        $methods = array();
        foreach ($interestedMethods as $methodName => $scope) {
            if ($scope & self::ACTIVE && $cr->hasMethod($methodName)) {
                $method = $cr->getMethod($methodName);
                if ($method->getDeclaringClass()->getName() == $cr->getName())
                    $methods[] = $method;
            }
        }

        return $methods;
    }

    private function getParentClasses() {
        $interestedMethods = $this->getInterestedMethodNames();
        $parents = array();
        $cr = $this->getPresenter()->getReflection()->getParentClass();
        while ($cr !== NULL && $cr->getName() != "Presenter" && $cr->getName() != "Nette\\Application\\UI\\Presenter") {
            $methods = array();
            foreach ($interestedMethods as $methodName => $scope) {
                if ($scope & self::PARENTS && $cr->hasMethod($methodName)) {
                    $method = $cr->getMethod($methodName);
                    if ($method->getDeclaringClass()->getName() == $cr->getName())
                        $methods[] = $method;
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

    private function getUsedComponentMethods($componentMethods) {
        return array_filter($componentMethods,
            function ($var) {
                return $var['isUsed'];
            }
        );
    }

    private function getUnusedComponentMethods($componentMethods) {
        return array_filter($componentMethods,
            function ($var) {
                return !$var['isUsed'];
            }
        );
    }

    private function getComponentMethods() {
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

    private function getActionMethodReflection() {
        $method = $this->getActionMethodName();
        if ($this->getPresenter()->getReflection()->hasMethod($method))
            return $this->getPresenter()->getReflection()->getMethod($method);
        else
            return NULL;
    }

    private function getRenderMethodReflection() {
        $method = $this->getRenderMethodName();
        if ($this->getPresenter()->getReflection()->hasMethod($method))
            return $this->getPresenter()->getReflection()->getMethod($method);
        else
            return NULL;
    }

    public static function getEditorLink($file, $line = 1) {
        if ($file instanceof Reflection\Method || $file instanceof Reflection\ClassType) {
            $line = $file->getStartLine();
            $file = $file->getFileName();
        }
        $line = (int)$line;

        return strtr(Diagnostics\Debugger::$editor, array('%file' => $file, '%line' => $line));
    }

}
