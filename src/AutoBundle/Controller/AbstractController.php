<?php
namespace AutoBundle\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View as ViewTemplate;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * Base Controller for classic crud
 */
abstract class AbstractController extends Controller implements ClassResourceInterface
{
    /** @var ContainerAwareEventDispatcher */
    protected $dispatcher;

    /** @var string */
    protected $entityName = null;

    /** @var array The ACL for each Action */
    protected $acl = [];

    /** @var array Default orderBy used in indexAction */
    protected $defaultOrder = ['id' => 'desc'];

    /** @var array Default filter used in indexAction */
    protected $defaultFilters = [];

    /** @var int */
    protected $defaultLimit = 20;

    /** @var null|array */
    protected $orderable = null;

    /** @var null|array */
    protected $filterable = null;

    /**
     * Replace __construct that doesn't exist in Symfony
     *
     * @param string $type
     */
    public function init($type = 'default')
    {
        $this->checkAcl($type);

        $this->dispatcher = new ContainerAwareEventDispatcher($this->container);
    }

    /**
     * List all entities
     *
     * @param Request $request The Request
     *
     * @return array
     *
     * @ViewTemplate()
     * @QueryParam(name="offset", requirements="\d+", nullable=true, description="Start pagination offset")
     * @QueryParam(name="limit", requirements="\d+", nullable=true, description="Number of entities to display")
     * @QueryParam(name="order", map=true, nullable=true, description="Sort order")
     *
     * @ApiDoc(
     *     output={"collection"=true}
     * )
     */
    public function cgetAction(Request $request)
    {
        $this->init('list');

        $search  = $this->getSearch($request);
        $filters = $this->getFilterBy($request);
        $orderBy = $this->getOrderBy($request);

        $limit  = $request->get('limit');
        $offset = $request->get('offset');

        if (!$limit && '0' !== $limit) {
            $limit = $this->defaultLimit;
        }
        if (!$offset) {
            $offset = 0;
        }

        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getDoctrine()->getManager()
            ->getRepository('AppBundle:'.$this->entityName);

        /** Check if repository implemented method */
        if (method_exists($repository, 'searchAndfindBy')) {
            $only = $request->get('_only');

            /** @var \AutoBundle\Repository\AbstractRepository $repository */
            $entities = $repository->searchAndfindBy(
                $search,
                $filters,
                $orderBy,
                $limit,
                $offset,
                $only
            );

            $paginator = $repository->getPaginator();
        } else {
            $entities = $repository->findBy(
                $filters,
                $orderBy,
                $limit,
                $offset
            );

            $paginator = null;
        }

        $total   = $paginator ? $paginator->getTotalCount() : null;
        $current = $paginator ? $paginator->getSearchCount() : null;

        $return = [
            'recordsTotal'    => $total,
            'recordsFiltered' => $current,
            'data'            => $entities,
        ];

        return $return;
    }

    /**
     * Return the search used in the list, based on Request and Session
     *
     * @param Request $request
     *
     * @return array|null
     */
    protected function getSearch(Request $request)
    {
        $search = $request->get('search');

        if (null !== $search) {

            if (!is_array($search)) {
                $search = ['value' => $search, 'type' => 'contains'];
            }

            if (!isset($search['type'])) {
                $search['type'] = 'contains';
            }
        }

        return $search;
    }

    /**
     * Return the filters array used in the list, based on Request and Session
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getFilterBy(Request $request)
    {
        if (isset($this->filterable)) {
            $filters = [];

            foreach ($this->filterable as $name) {

                $filter = $request->get($name);
                $filters[$name] = $filter;
            }

            $filters = array_filter(
                $filters,
                function ($v) {
                    return !(null == $v && '0' !== $v);
                }
            );
        } else {
            $filters = $this->defaultFilters;
        }

        return $filters;
    }

    /**
     * Return the orderBy array used in the list
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getOrderBy(Request $request)
    {
        if (isset($this->orderable)) {
            $orderBy = $request->get('orderBy');

            if (!is_array($orderBy)) {
                $orderBy = $this->defaultOrder;
            }
        } else {
            $orderBy = $this->defaultOrder;
        }

        return $orderBy;
    }
    /** /Index */

    /**
     * Get entities count
     *
     * @param Request $request The Request
     *
     * @return int
     *
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function getCountAction(Request $request)
    {
        $this->init('count');

        /** @var \AutoBundle\Repository\AbstractRepository $repository */
        $repository = $this->getDoctrine()->getManager()
            ->getRepository('AppBundle:'.$this->entityName);

        return $repository->count();
    }

    /**
     * Return an existing entity
     *
     * @param integer $id      The entity id
     * @param Request $request The Request
     *
     * @return array
     *
     * @ViewTemplate()
     * @ApiDoc(output={})
     */
    public function getAction($id, Request $request)
    {
        $this->init('get');

        $em = $this->getDoctrine()->getManager();

        if (!$entity = $em->getRepository('AppBundle:'.$this->entityName)->find($id)) {
            return $this->notFound();
        }

        return $entity;
    }

    /**
     * Save a new entity
     *
     * @param Request $request The Request
     *
     * @return array
     *
     * @ViewTemplate(statusCode=Response::HTTP_CREATED)
     * @ApiDoc(
     *     statusCodes = {
     *        201 = "Creation successful",
     *        400 = "Invalid form"
     *    }
     * )
     */
    public function postAction(Request $request)
    {
        $this->init('post');

        $entityName = 'AppBundle\Entity\\'.$this->entityName;
        $formName   = 'AppBundle\Form\\'.$this->entityName.'Type';

        $entity = new $entityName();
        $form   = $this->createForm(
            $formName,
            $entity
        );

        $this->triggerEvent(
            'onCreateBeforeSubmit',
            [
                'entity'  => $entity,
                'request' => $request,
            ]
        );

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $this->triggerEvent(
                'onCreateBeforeSave',
                [
                    'entity' => $entity,
                ]
            );

            $em = $this->getDoctrine()->getManager();

            $em->persist($entity);
            $em->flush();

            $this->triggerEvent(
                'onCreateAfterSave',
                [
                    'entity' => $entity,
                ]
            );

            return $entity;
        } else {
            return $form;
        }
    }

    /**
     * Save an existing entity
     *
     * @param integer $id      The entity id
     * @param Request $request The Request
     *
     * @@return object|\Symfony\Component\Form\Form|JsonResponse
     *
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function putAction($id, Request $request)
    {
        $this->init('put');

        return $this->putPatch($id, $request);
    }

    /**
     * Patch an existing entity
     *
     * @param integer $id      The entity id
     * @param Request $request The Request
     *
     * @@return object|\Symfony\Component\Form\Form|JsonResponse
     *
     * @ViewTemplate()
     * @ApiDoc()
     */
    public function patchAction($id, Request $request)
    {
        $this->init('patch');

        return $this->putPatch($id, $request, true);
    }

    /**
     * Method to manage put or path entity
     *
     * @param integer $id
     * @param Request $request
     * @param bool    $clearMissing
     *
     * @return object|\Symfony\Component\Form\Form|JsonResponse
     */
    protected function putPatch($id, Request $request, $clearMissing = false)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$entity = $em->getRepository('AppBundle:'.$this->entityName)->find($id)) {
            return $this->notFound();
        }

        $this->triggerEvent(
            'onUpdateBeforeSubmit',
            [
                'entity'  => $entity,
                'request' => $request,
            ]
        );

        $formName = 'AppBundle\Form\\'.$this->entityName.'Type';
        $form     = $this->createForm(
            $formName,
            $entity
        );

        $form->submit($request->request->all(), $clearMissing);

        if ($form->isValid()) {
            $this->triggerEvent(
                'onUpdateBeforeSave',
                [
                    'entity' => $entity,
                ]
            );

            $em = $this->getDoctrine()->getManager();

            $em->merge($entity);
            $em->flush();

            $this->triggerEvent(
                'onUpdateAfterSave',
                [
                    'entity' => $entity,
                ]
            );

            return $entity;
        } else {
            return $form;
        }
    }
    
    /**
     * Delete an entity
     *
     * @param integer $id      The entity id
     * @param Request $request The Request
     *
     * @return array
     *
     * @ViewTemplate(statusCode=Response::HTTP_NO_CONTENT)
     * @ApiDoc()
     */
    public function deleteAction($id, Request $request)
    {
        $this->init('delete');

        $em = $this->getDoctrine()->getManager();

        if ($entity = $em->getRepository('AppBundle:'.$this->entityName)->find($id)) {
            $em->remove($entity);
            $em->flush();
        }
    }

    /**
     * Return a PDF version of the entity
     *
     * @param $id
     * @param $request
     *
     * @return Response
     *
     * @ApiDoc()
     */
    public function getPdfAction($id, Request $request)
    {
        $this->init('pdf');

        $html = $this->returnPrint($id, $request);

        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$this->entityName.'.pdf"',
            ]
        );
    }

    /**
     * Return a print version of the entity
     *
     * @param $id
     * @param $request
     *
     * @return Response
     *
     * @ApiDoc()
     */
    public function getPrintAction($id, Request $request)
    {
        $this->init('print');

        $html = $this->returnPrint($id, $request);

        return new Response($html);
    }

    /**
     * @param         $id
     * @param Request $request
     *
     * @return string
     */
    protected function returnPrint($id, Request $request)
    {
        // TODO
    }

    /**
     * Return a 404 if the entity is not found
     *
     * @return static
     */
    protected function notFound()
    {
        return View::create(['message' => 'Entity not found'], Response::HTTP_NOT_FOUND);
    }

    /**
     * @param string $name
     */
    protected function checkAcl($name = 'default')
    {
        if ($this->acl)
        {
            if (isset($this->acl[$name]))
            {
                $this->denyAccessUnlessGranted($this->acl[$name]);
            }
            elseif (isset($this->acl['default']))
            {
                $this->denyAccessUnlessGranted($this->acl['default']);
            }
        }
    }

    /**
     * Trigger an event
     *
     * @param string $event
     * @param array  $arguments
     */
    protected function triggerEvent($event, array $arguments = [])
    {
        $generic   = new GenericEvent($event, $arguments);
        $eventName = $this->entityName.'.'.$event;

        $this->dispatcher->dispatch($eventName, $generic);
    }

    /**
     * Get the env
     *
     * @return string
     */
    protected function getEnv()
    {
        return $this->get('kernel')->getEnvironment();
    }


    public function getNSEntity ()
    {
        return 'AppBundle\Entity\\'.$this->entityName;
    }
}
