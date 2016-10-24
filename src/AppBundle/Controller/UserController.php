<?php
namespace AppBundle\Controller;

use AutoBundle\Controller\AbstractController;

use Symfony\Component\EventDispatcher\GenericEvent;

class UserController extends AbstractController
{
    protected $entityName = 'User';

    protected $acl = [
        'default' => 'ROLE_ADMIN'
    ];

    /**
     * @inheritdoc
     */
    public function init($type = 'default')
    {
        parent::init($type);

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
