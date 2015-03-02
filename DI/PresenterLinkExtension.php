<?php
/**
 * @author Jan Langer
 * @license MIT
 */

namespace PresenterLink\DI;

use Nette;
use Nette\DI;

class PresenterLinkExtension extends DI\CompilerExtension
{
    public function afterCompile(Nette\PhpGenerator\ClassType $class)
    {

        $container = $this->getContainerBuilder();

        if ($container->parameters['debugMode']) {
            $initialize = $class->methods['initialize'];

            $initialize->addBody(
                $container->formatPhp(
                    'Tracy\Debugger::' . (method_exists('Tracy\Debugger', 'getBar') ? 'getBar()' : '$bar') . '->addPanel(?, "presenter-link-panel");',
                    Nette\DI\Compiler::filterArguments(array(new DI\Statement('PresenterLink\Panel', ['appDir' => $container->parameters['appDir'],])))
                )
            );
        }
    }


} 