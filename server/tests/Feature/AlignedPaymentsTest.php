<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Category;
use App\Models\EmployerProfile;
use App\Models\CandidateProfile;
use App\Models\JobListing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlignedPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $employer;
    private User $candidate;
    private JobListing $job;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        // Create employer
        $this->employer = User::create([
            'name' => 'Acme Corp',
            'email' => 'employer@acme.com',
            'password' => bcrypt('password'),
            'role' => 'employer',
            'is_active' => true,
        ]);

        $employerProfile = EmployerProfile::create([
            'user_id' => $this->employer->id,
            'company_name' => 'Acme Corp',
        ]);

        // Create candidate
        $this->candidate = User::create([
            'name' => 'John Candidate',
            'email' => 'candidate@example.com',
            'password' => bcrypt('password'),
            'role' => 'candidate',
            'is_active' => true,
        ]);

        $candidateProfile = CandidateProfile::create([
            'user_id' => $this->candidate->id,
            'phone' => '+15555550199',
            'location' => 'San Francisco, CA',
        ]);

        // Create category
        $category = Category::create([
            'name' => 'Engineering',
        ]);

        // Create job
        $this->job = JobListing::create([
            'employer_profile_id' => $employerProfile->id,
            'category_id' => $category->id,
            'title' => 'Software Engineer',
            'description' => 'Great role.',
            'work_type' => 'full-time',
            'status' => 'approved',
        ]);

        // Create application
        $this->application = Application::create([
            'job_listing_id' => $this->job->id,
            'candidate_profile_id' => $candidateProfile->id,
            'cover_letter' => 'I love coding.',
            'status' => 'accepted',
        ]);
    }

    public function test_get_checkout_details(): void
    {
        $response = $this->actingAs($this->employer, 'sanctum')
            ->getJson("/api/v1/applications/{$this->application->id}/checkout");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->application->id,
                    'candidate_name' => 'John Candidate',
                    'job_title' => 'Software Engineer',
                    'amount' => 49.00,
                    'currency' => 'USD',
                    'status' => 'accepted',
                ],
            ]);
    }

    public function test_get_checkout_details_unauthorized(): void
    {
        // Another employer
        $otherEmployer = User::create([
            'name' => 'Other Corp',
            'email' => 'other@acme.com',
            'password' => bcrypt('password'),
            'role' => 'employer',
            'is_active' => true,
        ]);
        EmployerProfile::create([
            'user_id' => $otherEmployer->id,
            'company_name' => 'Other Corp',
        ]);

        $response = $this->actingAs($otherEmployer, 'sanctum')
            ->getJson("/api/v1/applications/{$this->application->id}/checkout");

        $response->assertStatus(403);
    }

    public function test_stripe_returns_500_if_not_configured(): void
    {
        config(['services.stripe.secret' => null]);

        $response = $this->actingAs($this->employer, 'sanctum')
            ->postJson("/api/v1/payments/stripe", [
                'application_id' => $this->application->id,
            ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Stripe is not configured.',
            ]);
    }

    public function test_paypal_returns_500_if_not_configured(): void
    {
        config(['services.paypal.client_id' => null]);

        $response = $this->actingAs($this->employer, 'sanctum')
            ->postJson("/api/v1/payments/paypal", [
                'application_id' => $this->application->id,
            ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'PayPal is not configured.',
            ]);
    }

    public function test_get_contact_fails_if_unpaid(): void
    {
        $response = $this->actingAs($this->employer, 'sanctum')
            ->getJson("/api/v1/applications/{$this->application->id}/contact");

        $response->assertStatus(403);
    }

    public function test_get_contact_succeeds_if_paid(): void
    {
        // Update application to paid
        $this->application->update(['status' => 'paid']);

        $response = $this->actingAs($this->employer, 'sanctum')
            ->getJson("/api/v1/applications/{$this->application->id}/contact");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'email' => 'candidate@example.com',
                    'phone' => '+15555550199',
                ],
            ]);
    }
}
