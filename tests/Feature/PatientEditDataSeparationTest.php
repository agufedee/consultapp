<?php

namespace Tests\Feature;

use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Models\Contact;
use App\Models\Gender;
use App\Models\Patient;
use App\Models\PersonalData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class PatientEditDataSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_patient_updates_gender_in_personal_data(): void
    {
        $user = User::factory()->create();

        $genderA = Gender::query()->create(['name' => 'Masculino']);
        $genderB = Gender::query()->create(['name' => 'Femenino']);

        $contact = Contact::query()->create([
            'email' => 'paciente@test.com',
            'phone' => '1111-1111',
        ]);

        $personalData = PersonalData::query()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'contact_id' => $contact->id,
            'address' => 'Calle 123',
            'birth_date' => '1990-01-01',
            'gender_id' => $genderA->id,
            'dni' => '40123456',
        ]);

        $patient = Patient::query()->create([
            'personal_data_id' => $personalData->id,
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(EditPatient::class, ['record' => $patient->getKey()])
            ->fillForm([
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'dni' => '40123456',
                'birth_date' => '1990-01-01',
                'gender_id' => $genderB->id,
                'address' => 'Calle 123',
                'email' => 'paciente@test.com',
                'phone' => '1111-1111',
                'active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('personal_data', [
            'id' => $personalData->id,
            'gender_id' => $genderB->id,
        ]);
    }

    public function test_edit_patient_does_not_persist_gender_id_on_patients_table(): void
    {
        $user = User::factory()->create();

        $genderA = Gender::query()->create(['name' => 'Masculino']);
        $genderB = Gender::query()->create(['name' => 'Femenino']);

        $contact = Contact::query()->create([
            'email' => 'paciente2@test.com',
            'phone' => '2222-2222',
        ]);

        $personalData = PersonalData::query()->create([
            'first_name' => 'Ana',
            'last_name' => 'Gómez',
            'contact_id' => $contact->id,
            'address' => 'Calle 456',
            'birth_date' => '1992-05-20',
            'gender_id' => $genderA->id,
            'dni' => '38999888',
        ]);

        $patient = Patient::query()->create([
            'personal_data_id' => $personalData->id,
            'active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(EditPatient::class, ['record' => $patient->getKey()])
            ->fillForm([
                'first_name' => 'Ana',
                'last_name' => 'Gómez',
                'dni' => '38999888',
                'birth_date' => '1992-05-20',
                'gender_id' => $genderB->id,
                'address' => 'Calle 456',
                'email' => 'paciente2@test.com',
                'phone' => '2222-2222',
                'active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'personal_data_id' => $personalData->id,
            'active' => true,
        ]);

        $columns = Schema::getColumnListing('patients');

        $this->assertNotContains('gender_id', $columns);
    }
}
