<?php

namespace GodsDev\mycmsprojectnamespace;

use GodsDev\MyCMS\MyCMS;
use GodsDev\MyCMS\MyCommon;
//TODO check if beberlei/assert was successfully replaced by Webmozart, check 2 Assert::email below
use Webmozart\Assert\Assert;

class Mail extends MyCommon
{
    use \Nette\SmartObject;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     *
     * @var \Swift_Mailer
     */
    private $mailer = null;

    /**
     * @param MyCMS $MyCMS
     * @param array $options overrides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        $this->logger = $this->MyCMS->logger;
        $this->mailer = $this->getMailerInstance();
    }

    public function getMailerInstance()
    {
//        if(!is_null($this->mailer)) {
//            $this->logger->error('Attempt to reinitiate mailer instance.');
//        }
        // Create the Transport
        $this->logger->debug("SMTP transport uses " . SMTP_HOST . ":" . SMTP_PORT);
        $transport = \Swift_SmtpTransport::newInstance(SMTP_HOST, SMTP_PORT)
            ->setUsername('')
            ->setPassword('')
        ;

        /*
          You could alternatively use a different transport such as Sendmail or Mail:

          // Sendmail
          $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');

          // Mail
          $transport = Swift_MailTransport::newInstance();
         */

        // Create the Mailer using your created Transport
        return \Swift_Mailer::newInstance($transport);

//        // Create a message
//        $message = Swift_Message::newInstance($subject)
//                ->setFrom(array($mailFromAdress => $mailFromName))
//                ->setTo(array($mailAdressee))
//                ->setBody(file_get_contents($filenameTxt))
//                // And optionally an alternative body
//                ->addPart(file_get_contents($filename), 'text/html')
//        ;
//
//        // Send the message
//        $result = $mailer->send($message);
//
//        echo("{$mailAdressee} {$timestamp} {$filename}");
    }

    /**
     *
     * @param string $to
     * @param string $subject
     * @param string $messageTxt
     * @param array $options
     * @return int|false
     */
    public function sendMail($to, $subject, $messageTxt, array $options = [])
    {
        Assert::email(NOTIFY_FROM_ADDRESS, 'E-mail sender');
        Assert::email($to, 'E-mail recipient');
        if (defined('MAIL_SENDING_ACTIVE') && MAIL_SENDING_ACTIVE === false) {
            $this->logger->info("NOT SENDING {$to}/{$subject}/{$messageTxt}");
            return false;
        }

        //TODO test the sending logic here

        $timestamp = date(DATE_RSS);

        $subject = $subject . ' ' . $timestamp; // @todo debug - to see which mail was delivered
//        $filename = 'temp/' . 'test.html';
//        $filenameTxt = 'temp/' . 'test.txt';

        $message = \Swift_Message::newInstance($subject)
            ->setFrom(array(NOTIFY_FROM_ADDRESS => NOTIFY_FROM_NAME))
            ->setTo(array($to))
            ->setBody(
//                        file_get_contents($filenameTxt)
//                "Test sender at {$timestamp} "
                $messageTxt
            )
        // And optionally an alternative body
        //              ->addPart(file_get_contents($filename), 'text/html')
        ;
        $this->logger->info("SENDING {$to}/{$subject}/{$message}");
        // Send the message
        $successfulRecipientCount = $this->mailer->send($message);

        //log after sending
        $this->logger->info("SENT {$to}/{$subject}/{$message} | count of recipients: [{$successfulRecipientCount}]");
        return $successfulRecipientCount;
    }
}
