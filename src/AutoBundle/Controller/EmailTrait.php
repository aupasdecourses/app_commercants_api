<?php
namespace AutoBundle\Controller;

trait EmailTrait
{
    /**
     * @param string       $subject
     * @param string       $body
     * @param string|array $from
     * @param string|array $to
     * @param string|array $cc
     * @param string|array $bcc
     *
     * @return \Swift_Message
     */
    public function prepareEmail($subject, $body, $from, $to, $cc = null, $bcc = null)
    {
        $message = \Swift_Message::newInstance($subject, $body); // Use 'text/html' to enable HTML email

        if (!is_array($from))
        {
            $from = explode('|', $from);
        }
        if (!is_array($to))
        {
            $to = explode('|', $to);
        }

        if (!isset($from[1]))
        {
            $from[1] = null;
        }
        if (!isset($to[1]))
        {
            $to[1] = null;
        }

        $message->setFrom($from[0], $from[1]);
        $message->setTo($to[0], $to[1]);

        if ($cc)
        {
            $message->setCc($cc);
        }
        if ($bcc)
        {
            $message->setBcc($bcc);
        }

        return $message;
    }
}