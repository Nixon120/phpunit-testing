<?php

namespace Events\Listeners\Report;

use AllDigitalRewards\AMQP\MessagePublisher;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterInterface;
use Controllers\Report\InputNormalizer;
use Entities\Event;
use Entities\Report as ReportEntity;
use Entities\Sftp;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use Services\Report as Report;
use Services\Sftp\SftpService;
use Traits\LoggerAwareTrait;

class Request extends AbstractListener
{
    use LoggerAwareTrait;

    /**
     * @var Report\ServiceFactory
     */
    private $reportFactory;

    /**
     * @var Report\AbstractReport
     */
    private $reportService;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var ReportEntity
     */
    private $report;

    /**
     * @var null|array
     */
    private $reportData;

    /**
     * @var string
     */
    private $reportFileName;

    /**
     * @var int
     */
    private $reportCount;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var \DateTime
     */
    private $startDateTime;

    /**
     * @var \DateTime
     */
    private $endDateTime;

    public function __construct(
        MessagePublisher $publisher,
        Report\ServiceFactory $factory
    )
    {

        parent::__construct($publisher);
        $this->reportFactory = $factory;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

    /**
     * @param Event $event
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @param EventInterface|Event $event
     * @return bool
     */
    public function handle(EventInterface $event): bool
    {
        $this->setEvent($event);
        /** @var Event $event */
        if ($this->generateReport() === true) {
            return true;
        }

        $this->logReportGenerationFailure();
        return false;
    }

    /**
     * Logs report failure
     */
    private function logReportGenerationFailure()
    {
        $this->getLogger()->error(
            'Reports',
            [
                'subsystem' => 'Report Queue',
                'action' => 'generate',
                'success' => false,
                'organization' => $this->getReport()->getOrganization(),
                'program' => $this->getReport()->getProgram(),
                'report' => $this->getReport()->getReportName(),
                'report_id' => $this->getReport()->getId()
            ]
        );
    }

    /**
     * @param ReportEntity $report
     */
    public function setReport(ReportEntity $report)
    {
        $this->report = $report;
    }

    /**
     * @return ReportEntity
     */
    public function getReport(): ReportEntity
    {
        if ($this->report === null) {
            $reportId = $this->getEvent()->getEntityId();

            $report = $this->reportFactory
                ->getReportRepository()
                ->getReportById($reportId);
            $this->setReport($report);
        }

        return $this->report;
    }

    private function getStartDateTime():\DateTime
    {
        if($this->startDateTime === null) {
            $startDate = $this->getReportService()->getFilter()->getInput()['start_date'];
            if ($startDate === null || trim($startDate) === "") {
                $startDate = '2000-01-01';
            }

            $this->startDateTime = new \DateTime($startDate);
        }

        return $this->startDateTime;
    }

    private function getEndDateTime():\DateTime
    {
        if($this->endDateTime === null) {
            $endDate = $this->getReportService()->getFilter()->getInput()['end_date'];

            if ($endDate === null || trim($endDate) === "") {
                $endDate = 'now';
            }
            $this->endDateTime = new \DateTime($endDate);
        }

        return $this->endDateTime;
    }

    private function getReportTitleSegment()
    {

        $style = (new StyleBuilder)
            ->setFontBold()
            ->setFontSize(16)
            ->build();

        return [
            WriterEntityFactory::createRow([
                WriterEntityFactory::createCell(ucfirst($this->getReport()->getProgram()), $style),
            ]),
            WriterEntityFactory::createRow([
                WriterEntityFactory::createCell($this->getReport()->getReportName(), $style),
            ]),
            WriterEntityFactory::createRow([
                WriterEntityFactory::createCell('As of ' . $this->getEndDateTime()->format('M d, Y'), $style),
            ]),
            WriterEntityFactory::createRow([
                WriterEntityFactory::createCell(
                    $this->getStartDateTime()->format('m/d/Y') . ' - ' . $this->getEndDateTime()->format('m/d/Y'),
                    $style
                ),
            ]),
            WriterEntityFactory::createRow([
                WriterEntityFactory::createCell('', $style),
            ])
        ];




    }

    /**
     * @return WriterInterface|null
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    private function getWriter()
    {
        if ($this->writer === null) {

            if ($this->getReport()->getFormat() === 'csv') {
                $writer = WriterEntityFactory::createCSVWriter();
            } else {
                $writer = WriterEntityFactory::createXLSXWriter();
            }

            $writer->openToFile('php://output');
            $writer->addRows($this->getReportTitleSegment());
            $this->setWriter($writer);
        }

        return $this->writer;
    }

    /**
     * @param WriterInterface $writer
     */
    private function setWriter(WriterInterface $writer)
    {
        $this->writer = $writer;
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getReportFileName()
    {
        if ($this->reportFileName === null) {
            $this->reportFileName = (new \DateTime)->format('Y-m-d-His') . '_'
                . $this->getReport()->getOrganization() . '_';

            if ($this->getReport()->getProgram() !== null) {
                $this->reportFileName .= $this->getReport()->getProgram() . '_';
            }

            $this->reportFileName .= $this->getReport()->getId() . '_'
                . $this->getReportService()->getReportName() . '.'
                . $this->getReport()->getFormatExtension();
        }

        return str_replace(' ', '_', $this->reportFileName);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getReportData(): array
    {
        if ($this->reportData === null) {
            $reportService = $this->getReportService();
            $reportService->setLimitResultCount(false);
            $reportResponse = $reportService->getReportData();
            $data = $reportResponse->getReportData();
            $headers = $reportService->getReportHeaders();
            array_unshift($data, $headers);
            $this->reportData = $data;
            $this->reportCount = $reportResponse->getTotalRecords();
        }

        return $this->reportData;
    }

    /**
     * @return Report\AbstractReport
     * @throws \Exception
     */
    private function getReportService()
    {
        if (is_null($this->reportService)) {
            $input = $this->getReport()->getParameters();
            $normalizer = new InputNormalizer($input);
            $reportClass = '\\Services\\Report\\' . $this->getReport()->getReportClass();
            $reportFilter = '\\Services\\Report\\' . $this->getReport()->getReportClass() . 'FilterNormalizer';
            /** @var Report\AbstractReport $service */
            $service = new $reportClass($this->reportFactory);
            $filter = new $reportFilter($input);
            $service->setInputNormalizer($normalizer);
            $service->setFilter($filter);

            $this->reportService = $service;
        }

        return $this->reportService;
    }

    /**
     * @return bool
     */
    private function generateReport(): bool
    {
        if ($this->getReport() === null) {
            // The report entry was deleted, no need to keep this.
            return true;
        }

        if ($this->getReport()->isProcessed()) {
            // The report is already processed
            return true;
        }

        if ($this->generate() === true) {
            // The report generation was a success
            $this->setReportAsProcessed();
            return true;
        }

        // We're not going to requeue reports, as it wouldn't benefit anything.
        // Perhaps we could requeue up to 1x, incase of some weird error?
        return false;
    }

    private function generate()
    {
        ob_start();
        $reportFileNameWithExtension = $this->getReportFileName();
        foreach ($this->getReportData() as $data) {
            $this->getWriter()->addRow(WriterEntityFactory::createRowFromArray($data));
        }

        $this->getWriter()->close();
        $report = ob_get_clean();
        return $this->reportFactory
            ->getFilesystem('reports')
            ->put($reportFileNameWithExtension, $report);
    }

    /**
     * Set the report as processed
     */
    private function setReportAsProcessed()
    {
        $report = $this->getReport();
        $count = $this->reportCount;
        $report->setProcessed(1);
        $report->setResultCount($count);
        $report->setAttachment($this->getReportFileName());
        $this->reportFactory->getReportRepository()
            ->update($report->getId(), $report->toArray());

        //if SFTP publish it here!
        if (is_null($report->getParameters()['sftp'] ?? null) === false) {
            $sftpId = $report->getParameters()['sftp'];
            $published = $this->getSftpPublisher($sftpId, $report)->publish();

            $parameters = $report->getParameters();
            $parameters['sftp_published'] = $published === true ? 1 : 0;
            $report->setParameters(json_encode($parameters));
            $this->reportFactory->getReportRepository()
                ->update($report->getId(), $report->toArray());
        }
    }

    /**
     * @param string $sftpId
     * @param ReportEntity $report
     * @return SftpService
     * @throws \Exception
     */
    private function getSftpPublisher(string $sftpId, ReportEntity $report): SftpService
    {
        $sftpConfig = $this->reportFactory->getSftpRepository()
            ->getSftpById($sftpId);

        $sftpPublisher = new SftpService;
        $sftpPublisher->setSftpConfig($sftpConfig);
        $sftpPublisher->setPath($this->buildSftpReportFilePath($sftpConfig, $report));
        $sftpPublisher->setReport($report);
        return $sftpPublisher;
    }

    /**
     * @param Sftp $sftpConfig
     * @param ReportEntity $report
     * @return string
     * @throws \Exception
     */
    private function buildSftpReportFilePath(Sftp $sftpConfig, ReportEntity $report): string
    {
        $now = new \DateTime('now');
        $filePath =
            $report->getOrganization()
            . '_'
            . $report->getProgram()
            . '_'
            . str_replace(" ", "", $report->getReportName())
            . '_'
            . $now->format('Ymd')
            . '.'
            . $report->getFormatExtension();

        $filePath = str_replace(' ', '_', $filePath);

        return $sftpConfig->getFilePath() . '/' . $filePath;
    }
}
