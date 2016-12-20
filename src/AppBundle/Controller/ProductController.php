<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;
use AutoBundle\Controller\EmailTrait;

use FOS\RestBundle\Controller\Annotations\FileParam;
use FOS\RestBundle\Controller\Annotations\View as ViewTemplate;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        'patch'   => 'ROLE_USER',
    ];

    protected $filterable = ['user'];

    /** @var null|array The name of the changed fields */
    private $changes = null;

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

                $title = 'Produit créé chez '.$entity->getUser()->getShopName();
                $body  = 'Le produit suivant a été créé par '.$this->getUser().', le '.date("Y-m-d").' :'.PHP_EOL
                    .'SKU : '.$entity->getSku().PHP_EOL
                    .'Nom du produit : '.$entity->getName().PHP_EOL.PHP_EOL
                    .'Référence : '.$entity->getRef().PHP_EOL
                    .'Disponible : '.($entity->getAvailable() ? 'Oui' : 'Non').PHP_EOL
                    .'Sélection APDC : '.($entity->getSelected() ? 'Oui' : 'Non').PHP_EOL
                    .'Prix : '.$entity->getPrice().PHP_EOL
                    .'Unit : '.$entity->getPriceUnitValue().PHP_EOL
                    .'Description : '.$entity->getShortDescription().PHP_EOL
                    .'Poids portion : '.$entity->getPortionWeight().PHP_EOL
                    .'Nombre portion : '.$entity->getPortionNumber().PHP_EOL
                    .'Tax : '.$entity->getTaxValue().PHP_EOL
                    .'Origin : '.$entity->getOrigin().PHP_EOL
                    .'Bio : '.($entity->getBio() ? 'Oui' : 'Non').PHP_EOL
                ;

                $from = $this->getParameter('from_email');
                $to   = $this->getParameter('to_email');

                $message = $this->prepareEmail(
                    $title,
                    $body,
                    $from,
                    $to
                );

                if (!$result = $this->get('mailer')->send($message))
                {
                    // TODO: Maybe log error later?
                    // TODO: Maybe we can also add some notification to the user
                }
            }
        );
        $this->dispatcher->addListener(
            'Product.onUpdateBeforeSave',
            function (GenericEvent $event) {
                /** @var \AppBundle\Entity\Product $entity */
                if (!$entity = $event->getArgument('entity')) {
                    return;
                }

                /* Note: We could send the email here, but it's more secure to send it after the flush() */

                $em  = $this->getDoctrine()->getManager();
                $uow = $em->getUnitOfWork();
                $uow->computeChangeSets();

                $changes = $uow->getEntityChangeSet($entity);

                $this->changes = array_flip(array_keys($changes));
            }
        );
        $this->dispatcher->addListener(
            'Product.onUpdateAfterSave',
            function (GenericEvent $event) {
                /** @var \AppBundle\Entity\Product $entity */
                if (!$this->changes || !$entity = $event->getArgument('entity')) {
                    return;
                }

                $bodyChanges = [
                    'ref'              => 'Référence : '.$entity->getRef(),
                    'available'        => 'Disponible : '.($entity->getAvailable() ? 'Oui' : 'Non'),
                    'selected'         => 'Sélection APDC : '.($entity->getSelected() ? 'Oui' : 'Non'),
                    'price'            => 'Prix : '.$entity->getPrice(),
                    'priceUnit'        => 'Unit : '.$entity->getPriceUnitValue(),
                    'shortDescription' => 'Description : '.$entity->getShortDescription(),
                    'portionWeight'    => 'Poids portion : '.$entity->getPortionWeight(),
                    'portionNumber'    => 'Nombre portion : '.$entity->getPortionNumber(),
                    'tax'              => 'Tax : '.$entity->getTaxValue(),
                    'origin'           => 'Origin : '.$entity->getOrigin(),
                    'bio'              => 'Bio : '.($entity->getBio() ? 'Oui' : 'Non'),
                ];

                $title = 'Produit mise à jour chez '.$entity->getUser()->getShopName();
                $body  = 'Le produit suivant a été mis à jour par '.$this->getUser().', le '.date("Y-m-d").' :'.PHP_EOL
                    .'SKU : '.$entity->getSku().PHP_EOL
                    .'Nom du produit : '.$entity->getName().PHP_EOL.PHP_EOL
                    .implode(PHP_EOL, array_intersect_key($bodyChanges, $this->changes))
                ;

                $from = $this->getParameter('from_email');
                $to   = $this->getParameter('to_email');

                $message = $this->prepareEmail(
                    $title,
                    $body,
                    $from,
                    $to
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
     * @return object|Form|JsonResponse
     *
     * @FileParam(name="photoFile", nullable=true)
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function postUploadAction($id, Request $request, ParamFetcher $paramFetcher)
    {
        $this->init('patch');

        $request->request->add($paramFetcher->all());

        return $this->putPatch($id, $request, false);
    }
}
