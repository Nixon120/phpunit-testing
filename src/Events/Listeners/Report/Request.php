<?php
namespace Events\Listeners\Report;

use AllDigitalRewards\AMQP\MessagePublisher;
use Controllers\Report\InputNormalizer;
use Entities\Event;
use Entities\Report as ReportEntity;
use Entities\Sftp;
use Events\Listeners\AbstractListener;
use League\Event\EventInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
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
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var int
     */
    private $reportCount;

    /**
     * @var \PhpOffice\PhpSpreadsheet\Writer\IWriter
     */
    private $writer;

    public function __construct(
        MessagePublisher $publisher,
        Report\ServiceFactory $factory
    ) {
    
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

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        if ($this->spreadsheet === null) {
            $this->spreadsheet = new Spreadsheet;
        }

        return $this->spreadsheet;
    }

    /**
     * @param Spreadsheet $spreadsheet
     */
    public function setSpreadsheet(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

    /**
     * @return IWriter
     */
    private function getWriter()
    {
        if ($this->writer === null) {
            if ($this->getReport()->getFormat() === 'pdf') {
                $this->initializePdfWriter();
            }

            $writer = IOFactory::createWriter(
                $this->getSpreadsheet(),
                ucfirst($this->getReport()->getFormatExtension())
            );

            if ($this->getReport()->getFormat() === 'csv') {
                $this->setCsvOptionsOnWriter($writer);
            }

            $this->getSpreadsheet()
                ->getActiveSheet()
                ->getPageSetup()
                ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setFitToWidth(1)
                ->setFitToHeight(0);

            $this->setWriter($writer);
        }

        return $this->writer;
    }

    /**
     * @param IWriter $writer
     */
    private function setWriter(IWriter $writer)
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

    /**
     * @return bool
     */
    private function generate()
    {
        $reportFileNameWithExtension = $this->getReportFileName();
        $endDate = $this->getReport()->getParameters()['end_date'] ?? 'now';
        $endDate = new \DateTime($endDate);

        $this->getSpreadsheet()->getActiveSheet()->mergeCells("A1:G1")->setCellValue('A1', ucfirst($this->getReport()->getProgram()));
        $this->getSpreadsheet()->getActiveSheet()->mergeCells("A2:G2")->setCellValue('A2', $this->getReport()->getReportName());
        $this->getSpreadsheet()->getActiveSheet()->mergeCells("A3:G3")->setCellValue('A3', 'As of ' . $endDate->format('M d, Y'));
        $this->getSpreadsheet()
            ->getActiveSheet()
            ->getStyle("A1:G3")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $this->getSpreadsheet()
            ->getActiveSheet()
            ->getStyle("A1:G3")
            ->getFont()
            ->setBold( true )
            ->setSize(16);

        /** Load report data into sheet */
        $this->getSpreadsheet()
            ->getActiveSheet()
            ->fromArray($this->getReportData(), null, 'A5');

        $date = new \DateTime('now');
        $highestRow = $this->getSpreadsheet()->getActiveSheet()->getHighestRow();
        $highestRow += 2;
        $this->getSpreadsheet()->getActiveSheet()->mergeCells("A$highestRow:G$highestRow")->setCellValue("A$highestRow", $date->format('l, M d, Y h:i:s A'));
        $this->getSpreadsheet()
            ->getActiveSheet()
            ->getStyle("A$highestRow:G$highestRow")
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $this->getSpreadsheet()
            ->getActiveSheet()
            ->getStyle("A$highestRow:G$highestRow")
            ->getFont()
            ->setSize(10);

        ob_start();
        $this->getWriter()->save('php://output');
        $output = ob_get_clean();

        return $this->reportFactory
            ->getFilesystem('reports')
            ->put($reportFileNameWithExtension, $output);
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

        $filePath = str_replace(' ', '_' ,$filePath);

        return $sftpConfig->getFilePath() . '/' . $filePath;
    }

    /**
     * Initialize PDF writer
     */
    private function initializePdfWriter()
    {
        $sheet = $this->getSpreadsheet()
            ->getActiveSheet();

        /** Dompdf */
        $sheet->setShowGridLines(false);

        $sheet->getPageSetup()
            ->setFitToWidth(1);

        $sheet->getPageSetup()
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.25)
            ->setBottom(0.25)
            ->setLeft(0.1)
            ->setRight(0.1);

        $className = Dompdf::class;
        IOFactory::registerWriter('Pdf', $className);
    }

    /**
     * Set custom CSV options on writer
     *
     * @param IWriter $writer
     */
    private function setCsvOptionsOnWriter(IWriter $writer)
    {
        $writer
            ->setDelimiter(',')
            ->setEnclosure('"')
            ->setSheetIndex(0);
    }
}
