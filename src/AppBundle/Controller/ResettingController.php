<?php
namespace AppBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Description of ResettingController
 *
 * @author Carlos Mendoza <inhack20@tecnocreaciones.com>
 * @Route("resetting")
 */
class ResettingController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse|Response
     * @throws \Exception
     *
     * @Route("send-email")
     */
    public function sendEmailAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $data     = [];
            $response = new JsonResponse();

            $username = $request->request->get('username');

            /** @var $user \FOS\UserBundle\Model\UserInterface */
            $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

            if (null === $user) {
                $data['message'] = $this->trans('resetting.request.invalid_username', ['%username%' => $username]);
                $response->setData($data)->setStatusCode(400);

                return $response;
            }

            if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                $data['message'] = $this->trans('resetting.password_already_requested', []);
                $response->setData($data)->setStatusCode(400);

                return $response;
            }

            if (null === $user->getConfirmationToken()) {
                /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
            }

            $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
            $user->setPasswordRequestedAt(new \DateTime());
            $this->container->get('fos_user.user_manager')->updateUser($user);

            $data['message'] = $this->trans('resetting.check_email', ['%email%' => $username]);
            $response->setData($data);

            return $response;
        } else {
            return parent::sendEmailAction($request);
        }
    }
    /**
     * Reset user password
     *
     * @param Request $request
     * @param string  $token
     *
     * @return JsonResponse|Response
     * @throws NotFoundHttpException
     *
     * @Route("reset/{token}")
     */
    public function resetAction(Request $request, $token)
    {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
//                $response = $this->render('::resettingOk.html.twig', array(
//                    'token' => $token,
//                    'form' => $form->createView(),
//                ));

                return $this->redirect('http://commercants.aupasdecourses.com/');
            }

            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        return $this->render('FOSUserBundle:Resetting:reset.html.twig', array(
            'token' => $token,
            'form' => $form->createView(),
        ));
    }

    /**
     *
     * @param string $message
     * @param array  $params
     *
     * @return string
     */
    private function trans($message, array $params = [])
    {
        return $this->container->get('translator')->trans($message, $params, 'FOSUserBundle');
    }
}
