<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    protected $entityName = 'User';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        /**
         * - Create password onCreate
         */
        $this->dispatcher->addListener(
            'User.onUpdateBeforeSave',
            function (GenericEvent $event) {
                /** @var \AppBundle\Entity\User $entity */
                if (!$entity = $event->getArgument('entity')) {
                    return;
                }

                $userManager = $this->container->get('fos_user.user_manager');
                $userManager->updatePassword($entity);
            }
        );
    }
}
