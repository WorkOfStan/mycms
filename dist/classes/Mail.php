<?php

namespace WorkOfStan\mycmsprojectnamespace;

use Psr\Log\LoggerInterface;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Tracy\Debugger;
//TODO check if beberlei/assert was successfully replaced by Webmozart, check 2 Assert::email below
use Webmozart\Assert\Assert;
use WorkOfStan\MyCMS\MyCMS;
use WorkOfStan\MyCMS\MyCommon;

/**
 * Ready-made mail component
 * (Last MyCMS/dist revision: 2026-02-28, v0.5.1)
 */
class Mail extends MyCommon
{
    use \Nette\SmartObject;

    /** xx @ var bool */
    //private $atLeastPHP7;
    /** @var LoggerInterface */
    private $logger;
    /** @var Swift_Mailer|null */
    private $mailer = null;

    /**
     * @param MyCMS $MyCMS
     * @param array<mixed> $options overrides default values of properties
     */
    public function __construct(MyCMS $MyCMS, array $options = [])
    {
        parent::__construct($MyCMS, $options);
        //$this->atLeastPHP7 = (version_compare(PHP_VERSION, '7.0.0') >= 0);
        $this->logger = $this->MyCMS->logger;
        $this->mailer = $this->getMailerInstance();
    }

    /**
     *
     * @return Swift_Mailer
     */
    public function getMailerInstance()
    {
//        if(!is_null($this->mailer)) {
//            $this->logger->error('Attempt to reinitiate mailer instance.');
//        }
        // Create the Transport
        $this->logger->debug("SMTP transport uses " . SMTP_HOST . ":" . SMTP_PORT);
        $transport = //$this->atLeastPHP7 ? 
            (new Swift_SmtpTransport(SMTP_HOST, SMTP_PORT))
            //:
            /** xx@ phpstan-ignore-next-line as it is for PHP/5.6 */
            //Swift_SmtpTransport::newInstance(SMTP_HOST, SMTP_PORT)
            ;
        $transport
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
        return //$this->atLeastPHP7 ? 
            new Swift_Mailer($transport) 
            //:
            /** xx@ phpstan-ignore-next-line as it is for PHP/5.6 */
            //Swift_Mailer::newInstance($transport)
            ;

//        // Create a message (PHP/5.6)
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
     * @param array<string> $options for future use: attachment paths, template name, HTML
     * @return int|false The number of successful recipients. Can be 0 which indicates failure. (|false for PHP/5.6)
     */
    public function sendMail($to, $subject, $messageTxt, array $options = [])
    {
        Assert::email(NOTIFY_FROM_ADDRESS, 'E-mail sender');
        Assert::email($to, 'E-mail recipient');
        if (defined('MAIL_SENDING_ACTIVE') && MAIL_SENDING_ACTIVE === false) {
            Debugger::barDump("NOT SENDING to:{$to}", 'MAIL SENDING INACTIVE');
            $this->logger->info("NOT SENDING {$to}/{$subject}/{$messageTxt}");
            return false;
        }

        //TODO test the sending logic here

        $timestamp = date(DATE_RSS);

        $subject = $subject . ' ' . $timestamp; // @todo debug - to see which mail was delivered
//        $filename = 'temp/' . 'test.html';
//        $filenameTxt = 'temp/' . 'test.txt';

        $message = //$this->atLeastPHP7 ? 
            (new Swift_Message($subject))
            //:
            /** xx@ phpstan-ignore-next-line as it is for PHP/5.6 */
            // \Swift_Message::newInstance($subject)
            ;

        $message
            ->setFrom(array(NOTIFY_FROM_ADDRESS => NOTIFY_FROM_NAME))
            ->setTo(array($to))
            ->setBody(
                //file_get_contents($filenameTxt)
                //"Test sender at {$timestamp} "
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
