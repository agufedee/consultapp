<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\PatientPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $nutritionistRole;

    protected Role $secretaryRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin']);
        $this->nutritionistRole = Role::create(['name' => 'Nutricionista']);
        $this->secretaryRole = Role::create(['name' => 'Secretaria']);
    }

    /**
     * Helper to create a valid future appointment.
     */
    protected function createAppointment(int $patientId, int $userId, ?int $hourOffset = null): Appointment
    {
        $hour = $hourOffset ?? 10; // Default 10am
        $startDate = now()->addDays(2)->setTime($hour, 0);
        $endDate = (clone $startDate)->modify('+1 hour');

        return Appointment::create([
            'patient_id' => $patientId,
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => AppointmentStatus::AGENDADO->value,
        ]);
    }

    // ========================
    // Patient Policy Tests
    // ========================

    /** @test */
    public function nutritionist_can_only_view_own_patients(): void
    {
        $nutritionist1 = User::factory()->create(['role_id' => $this->nutritionistRole->id]);
        $nutritionist2 = User::factory()->create(['role_id' => $this->nutritionistRole->id]);

        // Patient attended by nutritionist1
        $ownPatient = Patient::factory()->create();
        $this->createAppointment($ownPatient->id, $nutritionist1->id);

        // Patient attended by nutritionist2
        $otherPatient = Patient::factory()->create();
        $this->createAppointment($otherPatient->id, $nutritionist2->id);

        $policy = new PatientPolicy;

        // Nutritionist1 can view their own patient
        $this->assertTrue($policy->view($nutritionist1, $ownPatient));

        // Nutritionist1 cannot view nutritionist2's patient
        $this->assertFalse($policy->view($nutritionist1, $otherPatient));
    }

    /** @test */
    public function secretary_can_view_all_patients(): void
    {
        $secretary = User::factory()->create(['role_id' => $this->secretaryRole->id]);
        $patient = Patient::factory()->create();

        $policy = new PatientPolicy;

        $this->assertTrue($policy->view($secretary, $patient));
    }

    /** @test */
    public function secretary_cannot_delete_patients(): void
    {
        $secretary = User::factory()->create(['role_id' => $this->secretaryRole->id]);
        $patient = Patient::factory()->create();

        $policy = new PatientPolicy;

        $this->assertFalse($policy->delete($secretary, $patient));
    }

    /** @test */
    public function admin_can_manage_everything(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $patient = Patient::factory()->create();

        $policy = new PatientPolicy;

        $this->assertTrue($policy->view($admin, $patient));
        $this->assertTrue($policy->update($admin, $patient));
        $this->assertTrue($policy->delete($admin, $patient));
    }

    /** @test */
    public function nutritionist_can_create_patients(): void
    {
        $nutritionist = User::factory()->create(['role_id' => $this->nutritionistRole->id]);

        $policy = new PatientPolicy;

        $this->assertTrue($policy->create($nutritionist));
    }

    /** @test */
    public function nutritionist_cannot_delete_own_patients(): void
    {
        $nutritionist = User::factory()->create(['role_id' => $this->nutritionistRole->id]);
        $patient = Patient::factory()->create();
        $this->createAppointment($patient->id, $nutritionist->id);

        $policy = new PatientPolicy;

        // Nutritionist cannot delete patients even if they are their own
        $this->assertFalse($policy->delete($nutritionist, $patient));
    }

    // ========================
    // Appointment Policy Tests
    // ========================

    /** @test */
    public function nutritionist_can_only_view_own_appointments(): void
    {
        $nutritionist1 = User::factory()->create(['role_id' => $this->nutritionistRole->id]);
        $nutritionist2 = User::factory()->create(['role_id' => $this->nutritionistRole->id]);
        $patient = Patient::factory()->create(['active' => true]);

        $ownAppointment = $this->createAppointment($patient->id, $nutritionist1->id, 10);
        $otherAppointment = $this->createAppointment($patient->id, $nutritionist2->id, 14);

        $policy = new AppointmentPolicy;

        $this->assertTrue($policy->view($nutritionist1, $ownAppointment));
        $this->assertFalse($policy->view($nutritionist1, $otherAppointment));
    }

    /** @test */
    public function secretary_cannot_update_appointments(): void
    {
        $secretary = User::factory()->create(['role_id' => $this->secretaryRole->id]);
        $patient = Patient::factory()->create(['active' => true]);
        $appointment = $this->createAppointment($patient->id, 1, 10);

        $policy = new AppointmentPolicy;

        $this->assertFalse($policy->update($secretary, $appointment));
    }

    /** @test */
    public function secretary_cannot_delete_appointments(): void
    {
        $secretary = User::factory()->create(['role_id' => $this->secretaryRole->id]);
        $patient = Patient::factory()->create(['active' => true]);
        $appointment = $this->createAppointment($patient->id, 1, 10);

        $policy = new AppointmentPolicy;

        $this->assertFalse($policy->delete($secretary, $appointment));
    }

    // ========================
    // User Policy Tests
    // ========================

    /** @test */
    public function only_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);
        $nutritionist = User::factory()->create(['role_id' => $this->nutritionistRole->id]);
        $secretary = User::factory()->create(['role_id' => $this->secretaryRole->id]);
        $user = User::factory()->create();

        $policy = new UserPolicy;

        // Admin can do everything
        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $user));
        $this->assertTrue($policy->delete($admin, $user));

        // Others cannot
        $this->assertFalse($policy->viewAny($nutritionist));
        $this->assertFalse($policy->viewAny($secretary));
        $this->assertFalse($policy->create($nutritionist));
        $this->assertFalse($policy->update($secretary, $user));
    }

    /** @test */
    public function admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role_id' => $this->adminRole->id]);

        $policy = new UserPolicy;

        $this->assertFalse($policy->delete($admin, $admin));
    }
}
