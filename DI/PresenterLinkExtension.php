<?php
/**
 * @author Jan Langer
 */
namespace PresenterLink\DI;

use Nette;
use Nette\DI;

class PresenterLinkExtension extends DI\CompilerExtension {
    public function afterCompile(Nette\PhpGenerator\ClassType $class) {

        $container = $this->getContainerBuilder();

        if($container->parameters['debugMode']) {
            $initialize = $class->methods['initialize'];

            $initialize->addBody($container->formatPhp(
                'Nette\Diagnostics\Debugger::'.(method_exists('Nette\Diagnostics\Debugger', 'getBar') ? 'getBar()' : '$bar').'->addPanel(?, "presenter-link-panel");',
                    Nette\DI\Compiler::filterArguments(array(
                        new DI\Statement('PresenterLink\Panel', [
                            'appDir' => $container->parameters['appDir'],
                            'latte' => new DI\Statement('@nette.latte')
                        ])
                    ))
            ));
        }
    }


} 