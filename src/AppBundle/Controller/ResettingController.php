<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use FOS\UserBundle\Controller\ResettingController as BaseController;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * @param type  $message
     * @param array $params
     *
     * @return type
     */
    private function trans($message, array $params = [])
    {
        return $this->container->get('translator')->trans($message, $params, 'FOSUserBundle');
    }
}
