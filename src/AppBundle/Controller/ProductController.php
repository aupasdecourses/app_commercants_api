<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    protected $entityName = 'Product';
}
