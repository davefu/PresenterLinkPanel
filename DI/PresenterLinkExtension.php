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
					'Tracy\Debugger::getBar()->addPanel(?, "presenter-link-panel");',
					Nette\DI\Compiler::filterArguments([
						new DI\Statement('PresenterLink\Panel', ['appDir' => $container->parameters['appDir'],])
					])
				)
			);
		}
	}

}