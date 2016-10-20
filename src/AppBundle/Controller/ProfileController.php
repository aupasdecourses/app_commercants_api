<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;

use FOS\RestBundle\Controller\Annotations\View as ViewTemplate;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProfileController extends Controller implements ClassResourceInterface
{
    /**
     * Return the user profile
     *
     * @param Request $request The Request
     *
     * @return object|JsonResponse
     *
     * @ViewTemplate()
     * @ApiDoc(output={})
     */
    public function getAction(Request $request)
    {
        return $this->getUser();
    }

    /**
     * Save the user profile
     *
     * @param Request $request The Request
     *
     * @@return object|\Symfony\Component\Form\Form|JsonResponse
     *
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function putAction(Request $request)
    {
        $entity = $this->getUser();
        $form   = $this->createForm(
            'AppBundle\Form\UserType',
            $entity
        );

        $form->submit($request->request->all(), false);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->merge($entity);
            $em->flush();

            return $entity;
        } else {
            return $form;
        }
    }
}
