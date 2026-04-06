<?php

namespace App\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php'; // Ensure Composer autoloader is included

use App\Core\Controller;
use App\Repositories\ReportRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

class DomPdfTemplateController extends Controller
{
    private $auditRepo;

    public function __construct()
    {
    parent::__construct();
        $this->auditRepo = new \App\Repositories\AuditLogRepository();
    }

    public function generatePdfTemplate()
    {
        $this->view("report_pdf_template/pdfTemplate", [
            "title" => "PDF Report Template"
        ], false);
    }

    public function generateLibraryReport()
    {
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            die("Start date and end date are required.");
        }

        $reportRepo = new ReportRepository();
        $campusId = $this->getCampusFilter();

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? null;
        $this->auditRepo->log($userId, 'GENERATE_REPORT', 'REPORTS', null, "Generated library report for period: $startDate to $endDate");

        $data = [
            'deletedBooks'     => $reportRepo->getDeletedBooksData($startDate, $endDate, $campusId),
            'circulatedBooks'  => $reportRepo->getCirculatedBooksData($startDate, $endDate, $campusId),
            'circulatedEquipments' => $reportRepo->getCirculatedEquipmentsData($startDate, $endDate, $campusId),
            'lostDamagedBooks' => $reportRepo->getLostDamagedBooksData($startDate, $endDate, $campusId),
            'topVisitors'      => $reportRepo->getTopVisitorsData($startDate, $endDate, $campusId),
            'topBorrowers'     => $reportRepo->getTopBorrowersData($startDate, $endDate, $campusId),
            'mostBorrowedBooks'=> $reportRepo->getMostBorrowedBooksData($startDate, $endDate, $campusId),
            'overdueSummary'   => $reportRepo->getOverdueSummaryData($startDate, $endDate, $campusId),
            'libraryResources' => $reportRepo->getLibraryResourcesData($campusId),
            'libraryVisits'    => $reportRepo->getLibraryVisitsData($startDate, $endDate, $campusId),
            'dateRange'        => [$startDate, $endDate],
        ];

        ob_start();
        extract($data);
        include __DIR__ . '/../Views/report_pdf_template/pdfTemplate.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Library_Report_' . date('Y-m-d') . '.pdf', ['Attachment' => true]);
    }
}


