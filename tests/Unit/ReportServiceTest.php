<?php

namespace Tests\Unit;

use App\Services\ReportService;
use PHPUnit\Framework\TestCase;

class ReportServiceTest extends TestCase
{
    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService;
    }

    /**
     * Test: ReportService instantiates
     */
    public function test_report_service_instantiates(): void
    {
        $this->assertInstanceOf(ReportService::class, $this->reportService);
    }

    /**
     * Test: getNewPatients method exists and is callable
     */
    public function test_get_new_patients_method_exists(): void
    {
        $this->assertTrue(method_exists($this->reportService, 'getNewPatients'));
        $this->assertTrue(is_callable([$this->reportService, 'getNewPatients']));
    }

    /**
     * Test: getPatientRetention method exists and is callable
     */
    public function test_get_patient_retention_method_exists(): void
    {
        $this->assertTrue(method_exists($this->reportService, 'getPatientRetention'));
        $this->assertTrue(is_callable([$this->reportService, 'getPatientRetention']));
    }

    /**
     * Test: getAbsenteeismSummary method exists and is callable
     */
    public function test_get_absenteeism_summary_method_exists(): void
    {
        $this->assertTrue(method_exists($this->reportService, 'getAbsenteeismSummary'));
        $this->assertTrue(is_callable([$this->reportService, 'getAbsenteeismSummary']));
    }
}
