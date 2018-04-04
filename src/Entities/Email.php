<?php
/**
 * Value Object to represent an email.
 *
 * For whatever reason I feel the need to explain this lameness.  This class must be replaced
 * with a solid implementation of an email class/object. I have zero time to accomplish this now
 * so here I am creating a placeholder...  (╯°□°）╯︵ ┻━┻)   ~Haq
 */

namespace Entities;

use JsonSerializable;

/**
 * Class Email
 * @package AllDigitalRewards\RA\Entity
 */
class Email implements JsonSerializable
{
    private $to;
    private $from;
    private $subject;
    private $htmlBody;
    private $textBody;
    private $service;
    private $serviceId;

    /**
     * Email constructor.
     * @param string $to To Email
     * @param string $from From Email
     * @param string $subject Subject
     * @param string $htmlBody HTML Body
     * @param string $textBody Text Body
     */
    public function __construct(
        $to,
        $from,
        $subject,
        $htmlBody,
        $textBody = ''
    ) {
        $this->setTo($to);
        $this->setFrom($from);
        $this->setSubject($subject);
        $this->setHtmlBody($htmlBody);
        $this->setTextBody($textBody);
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param string $htmlBody
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     */
    public function setTextBody($textBody)
    {
        $this->textBody = $textBody;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
