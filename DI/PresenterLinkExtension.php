<?php
/**
 * @author Jan Langer
 * @license MIT
 */

namespace PresenterLink\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\DI\PhpGenerator;
use Nette\PhpGenerator\ClassType;

class PresenterLinkExtension extends CompilerExtension
{
	public function afterCompile(ClassType $class)
	{

		$container = $this->getContainerBuilder();

		if ($container->parameters['debugMode']) {
			$initialize = $class->methods['initialize'];

			$generator = new PhpGenerator($container);
			$initialize->addBody(
				$generator->formatPhp(
					'Tracy\Debugger::getBar()->addPanel(?, "presenter-link-panel");',
					Helpers::filterArguments([
						new Statement('PresenterLink\Panel', [$container->parameters['appDir'], '@application'])
					])
				)
			);
		}
	}

}