<?php
namespace AutoBundle\Extractor;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseApiDocExtractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;


class ApiDocExtractor extends BaseApiDocExtractor
{
    protected $className;

    protected function extractData(ApiDoc $annotation, Route $route, \ReflectionMethod $method)
    {
        $this->setClassName($route->getDefault('_controller'));

        return parent::extractData($annotation, $route, $method);
    }

    protected function normalizeClassParameter($input)
    {
        if (!isset($input['class'])) {
            $input['class'] = $this->className;
        }

        return parent::normalizeClassParameter($input);
    }

    protected function setClassName($controller)
    {
        if (!preg_match('#(.+)::([\w]+)#', $controller, $matches)) {
            return false;
        }

        $controllerClass = $matches[1];

        if (!class_exists($controllerClass)) {
            return false;
        }

        /** @var \AutoBundle\Controller\AbstractController $controller */
        $controller = new $controllerClass();

        $this->className = $controller->getNSEntity();
    }
}
