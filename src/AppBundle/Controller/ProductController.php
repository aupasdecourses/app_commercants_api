<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;
use AutoBundle\Controller\EmailTrait;

use FOS\RestBundle\Controller\Annotations\FileParam;
use FOS\RestBundle\Controller\Annotations\View as ViewTemplate;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractController
{
    use EmailTrait;

    protected $entityName = 'Product';

    protected $acl = [
        'default' => 'ROLE_ADMIN',
        'list'    => 'ROLE_USER',
        'get'     => 'ROLE_USER',
        'post'    => 'ROLE_USER',
        'put'     => 'ROLE_USER',
    ];

    protected $filterable = ['user'];

    public function init($type = 'default')
    {
        parent::init($type);

        /**
         * - Force user for non-admin
         */
        $this->dispatcher->addListener(
            'Product.onCreateBeforeSubmit',
            function (GenericEvent $event) {
                if ($this->isGranted('ROLE_ADMIN')) {
                    return;
                }

                /** @var \Symfony\Component\HttpFoundation\Request $request */
                if (!$request = $event->getArgument('request')) {
                    return;
                }

                $request->request->add(
                    [
                        'user' => $this->getUser()->getId(),
                    ]
                );
            }
        );

        /**
         * - Send email when a product is created or updated
         */
        $this->dispatcher->addListener(
            'Product.onCreateAfterSave',
            function (GenericEvent $event) {
                /** @var \AppBundle\Entity\Product $entity */
                if (!$entity = $event->getArgument('entity')) {
                    return;
                }

                $message = $this->prepareEmail(
                    'Nouveau produit',
                    $entity->getUser()->getShopName().' à ajouté le produit : '.$entity->getName(),
                    ['noreplay@aupasdecourses.com' => 'Au Pas De Couses'],
                    ['prix@aupasdecourses.com' => 'Prix - Au Pas De Courses']
                );

                if (!$result = $this->get('mailer')->send($message))
                {
                    // TODO: Maybe log error later?
                    // TODO: Maybe we can also add some notification to the user
                }
            }
        );
        $this->dispatcher->addListener(
            'Product.onUpdateAfterSave',
            function (GenericEvent $event) {
                /** @var \AppBundle\Entity\Product $entity */
                if (!$entity = $event->getArgument('entity')) {
                    return;
                }

                $message = $this->prepareEmail(
                    'Produit mise à jour',
                    $entity->getUser()->getShopName().' à mise à jour le produit : '.$entity->getName(),
                    ['noreplay@aupasdecourses.com', 'Au Pas De Couses'],
                    ['prix@aupasdecourses.com', 'Prix - Au Pas De Courses']
                );

                if (!$result = $this->get('mailer')->send($message))
                {
                    // TODO: Maybe log error later?
                    // TODO: Maybe we can also add some notification to the user
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function getFilterBy(Request $request)
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return parent::getFilterBy($request);
        }

        $filters = parent::getFilterBy($request);
        $filters['user'] = $this->getUser()->getId();

        return $filters;
    }
    // TODO : Security, check user on GET and PUT for non-admin

    /**
     * Patch an existing entity
     *
     * @param integer $id      The entity id
     * @param Request $request The Request
     *
     * @return object|\Symfony\Component\Form\Form|JsonResponse
     *
     * @FileParam(name="photoFile", nullable=true)
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function postUploadAction($id, Request $request, ParamFetcher $paramFetcher)
    {
        $this->init('patch');

        return $this->putPatch($id, $request, false);
    }
}
