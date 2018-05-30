<?php
namespace Entities;

use Entities\Traits\TimestampTrait;

class Report extends Base
{
    use TimestampTrait;

    /**
     * @var string
     */
    public $organization;

    /**
     * @var string
     */
    public $program;

    /**
     * 1 = Enrollment,
     * 2 = Transaction,
     * 3 = Redemption,
     * 4 = Participant Summary,
     * 5 = Point Balance,
     * 6 = Sweepstake Drawing
     *
     * @var int
     */
    public $report;

    /**
     * The requested format; csv, xls, pdf
     *
     * @var string
     */
    public $format;

    /**
     * the filename
     *
     * @var string
     */
    public $attachment;

    /**
     * JSON of requested parameters
     *
     * @var string
     */
    public $parameters;

    /**
     * 0 = unprocessed
     * 1 = processed
     *
     * @var int
     */
    public $processed = 0;

    /**
     * @return string
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * @param string $organization
     */
    public function setOrganization(string $organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return null|string
     */
    public function getProgram():?string
    {
        if ($this->program === "") {
            return null;
        }

        return $this->program;
    }

    /**
     * @param null|string $program
     */
    public function setProgram(?string $program)
    {
        $this->program = $program;
    }

    /**
     * @return int
     */
    public function getReport(): int
    {
        return $this->report;
    }

    /**
     * @param int $report
     */
    public function setReport(int $report)
    {
        $this->report = $report;
    }

    /**
     * @return string
     */
    public function getReportName()
    {
        switch ($this->getReport()) {
            case 1:
                return 'Participant Enrollment';
                break;
            case 2:
                return 'Participant Transaction';
                break;
            case 3:
                return 'Participant Redemption';
                break;
            case 4:
                return 'Participant Summary';
                break;
            case 5:
                return 'Participant Point Balance';
                break;
            case 6:
                return 'Sweepstake Drawings';
                break;
            default:
                return 'Unknown';
                break;
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getReportClass()
    {
        switch ($this->getReport()) {
            case 1: //'Participant Enrollment':
                return 'Enrollment';
                break;
            case 2: //'Participant Transaction':
                return 'Transaction';
                break;
            case 3: //'Participant Redemption':
                return 'Redemption';
                break;
            case 4: //'Participant Summary':
                return 'ParticipantSummary';
                break;
            case 5: //'Participant Point Balance':
                return 'PointBalance';
                break;
            case 6: //'Sweepstake Drawings':
                return 'Sweepstake';
                break;
            default:
                throw new \Exception('Invalid report specification');
        }
    }

    /**
     * @return string
     */
    public function getFormatExtension(): string
    {
        $extension = 'csv';
        switch ($this->getFormat()) {
            case 'excel':
                $extension = 'xlsx';
                break;
            case 'pdf':
                $extension = 'pdf';
                break;
        }

        return $extension;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getAttachment(): string
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     */
    public function setAttachment(string $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * @return null|array
     */
    public function getParameters(): ?array
    {
        if ($this->parameters === null) {
            return [];
        }

        return json_decode($this->parameters, true);
    }

    /**
     * @param string $parameters
     */
    public function setParameters(string $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed == 1;
    }

    /**
     * @param int $processed
     */
    public function setProcessed(int $processed)
    {
        $this->processed = $processed;
    }
}