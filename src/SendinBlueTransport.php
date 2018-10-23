<?php

namespace Roykarte\LaravelSendinBlue;

use Illuminate\Mail\Transport\Transport;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\SMTPApi;
use SendinBlue\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Swift_Attachment;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;
use Swift_Mime_Headers_UnstructuredHeader;
use Exception;

class SendinBlueTransport extends Transport
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('SENDINBLUE_APIKEY'));

        $this->apiInstance = new SMTPApi(
            new Client(['verify' => false]),
            $config
        );
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $data = $this->buildData($message);

        $sendSmtpEmail = new SendSmtpEmail($data);

        if ($sendSmtpEmail->valid()) {
            try {
                $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);
                return $result;
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Exception when calling SMTPApi->sendTransacEmail: ' . $e->getMessage()];
            }
        } else {
            return ['success' => false, 'message' => 'This email is invalid'];
        }
    }

    private function buildData($message)
    {
        $data = [];

        $data['sender'] = ['name' => '60plusendus', 'email' => 'info@60plusendus.nl'];

        if ($message->getHeaders()) {
            $headers = $message->getHeaders()->getAll();

            foreach ($headers as $header) {
                if ($header instanceof Swift_Mime_Headers_UnstructuredHeader) {
                    $data['headers'][$header->getFieldName()] = $header->getValue();
                }
            }
        }

        $data['to'] = [];

        foreach ($message->getTo() as $email => $fake) {
            $data['to'][] = ['email' => $email];
        }

        if ($message->getSubject()) {
            $data['subject'] = $message->getSubject();
        }

        if ($message->getFrom()) {
            $from = $message->getFrom();
            reset($from);
            $key = key($from);
            $data['from'] = [$key, $from[$key]];
        }

        // set content
        if ($message->getContentType() == 'text/plain') {
            $data['textContent'] = $message->getBody();
        } else {
            $data['htmlContent'] = $message->getBody();
        }

        $children = $message->getChildren();
        foreach ($children as $child) {
            if ($child instanceof Swift_MimePart && $child->getContentType() == 'text/plain') {
                $data['textContent'] = $child->getBody();
            }
        }

        if (! isset($data['textContent'])) {
            $data['textContent'] = strip_tags($message->getBody());
        }
        // end set content

        if ($message->getCc()) {
            $data['cc'] = $message->getCc();
        }

        if ($message->getBcc()) {
            $data['bcc'] = $message->getBcc();
        }

        if ($message->getReplyTo()) {
            $replyTo = $message->getReplyTo();
            reset($replyTo);
            $key = key($replyTo);
            $data['replyto'] = [$key, $replyTo[$key]];
        }

        // attachment
        $attachment = [];
        foreach ($children as $child) {
            if ($child instanceof Swift_Attachment) {
                $filename = $child->getFilename();
                $content = chunk_split(base64_encode($child->getBody()));
                $attachment[$filename] = $content;
            }
        }

        if (count($attachment)) {
            $data['attachment'] = $attachment;
        }

        return $data;
    }
}
