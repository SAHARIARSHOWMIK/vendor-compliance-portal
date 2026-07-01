<?php

namespace Tests\Feature\Review;

use App\Enums\RoleName;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorUser;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DocumentTypeSeeder::class);
        Storage::fake('vendor_documents');
    }

    private function makeDocument(string $status = 'uploaded'): VendorDocument
    {
        $vendor   = Vendor::factory()->create(['category' => 'general_supplier', 'status' => 'under_review', 'risk_level' => 'low']);
        $docType  = DocumentType::where('slug', 'company_registration')->first();
        $uploader = User::factory()->create(['role' => RoleName::VendorUser]);
        VendorUser::factory()->create(['user_id' => $uploader->id, 'vendor_id' => $vendor->id]);

        return VendorDocument::factory()->create([
            'vendor_id'        => $vendor->id,
            'document_type_id' => $docType->id,
            'uploaded_by'      => $uploader->id,
            'status'           => $status,
        ]);
    }

    // --- Queue access ---

    public function test_reviewer_can_access_review_queue(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $this->actingAs($reviewer)->get(route('reviewer.queue'))->assertStatus(200);
    }

    public function test_compliance_admin_can_access_review_queue(): void
    {
        $admin = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $this->actingAs($admin)->get(route('reviewer.queue'))->assertStatus(200);
    }

    public function test_vendor_user_cannot_access_review_queue(): void
    {
        $user = User::factory()->create(['role' => RoleName::VendorUser]);
        $this->actingAs($user)->get(route('reviewer.queue'))->assertStatus(403);
    }

    public function test_auditor_cannot_access_review_queue(): void
    {
        $auditor = User::factory()->create(['role' => RoleName::Auditor]);
        $this->actingAs($auditor)->get(route('reviewer.queue'))->assertStatus(403);
    }

    // --- Document review page ---

    public function test_reviewer_can_view_document_review_page(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $this->actingAs($reviewer)
            ->get(route('reviewer.documents.show', $document))
            ->assertStatus(200)
            ->assertSee($document->documentType->name);
    }

    public function test_review_page_shows_version_history(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('reuploaded');

        DocumentVersion::factory()->create([
            'vendor_document_id' => $document->id,
            'vendor_id'          => $document->vendor_id,
            'document_type_id'   => $document->document_type_id,
            'uploaded_by'        => $document->uploaded_by,
            'version_number'     => 1,
            'file_path'          => 'demo/old_v1.pdf',
            'original_filename'  => 'old_v1.pdf',
            'mime_type'          => 'application/pdf',
            'file_size_kb'       => 500,
            'uploaded_at'        => now()->subHours(2),
        ]);

        $this->actingAs($reviewer)
            ->get(route('reviewer.documents.show', $document))
            ->assertStatus(200)
            ->assertSee('Version History');
    }

    // --- Approve ---

    public function test_reviewer_can_approve_document(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $response = $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved', 'comment' => '']);

        $response->assertRedirect(route('reviewer.queue'));
        $this->assertDatabaseHas('vendor_documents', ['id' => $document->id, 'status' => 'approved']);
        $this->assertDatabaseHas('reviews', ['vendor_document_id' => $document->id, 'decision' => 'approved', 'reviewer_id' => $reviewer->id]);
    }

    public function test_approval_creates_audit_log(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved']);

        $this->assertDatabaseHas('audit_logs', ['event_type' => 'document_approved', 'actor_id' => $reviewer->id, 'vendor_id' => $document->vendor_id]);
    }

    public function test_approval_triggers_compliance_recalculation(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved']);

        $this->assertDatabaseHas('compliance_checks', ['vendor_id' => $document->vendor_id]);
    }

    public function test_approval_creates_in_app_notification_for_vendor_user(): void
    {
        $reviewer   = User::factory()->create(['role' => RoleName::Reviewer]);
        $document   = $this->makeDocument('uploaded');
        $vendorUser = $document->vendor->vendorUsers()->with('user')->first()->user;

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved']);

        $this->assertDatabaseHas('notifications', ['user_id' => $vendorUser->id, 'type' => 'success', 'vendor_id' => $document->vendor_id]);
    }

    // --- Reject ---

    public function test_reviewer_can_reject_document_with_comment(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'rejected', 'comment' => 'Account number is unclear. Please reupload.']);

        $this->assertDatabaseHas('vendor_documents', ['id' => $document->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('reviews', ['decision' => 'rejected', 'comment' => 'Account number is unclear. Please reupload.']);
    }

    public function test_rejection_requires_a_comment(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('uploaded');

        $response = $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'rejected', 'comment' => '']);

        $response->assertSessionHasErrors('comment');
        $this->assertDatabaseHas('vendor_documents', ['id' => $document->id, 'status' => 'uploaded']);
    }

    // --- Correction requested ---

    public function test_reviewer_can_request_correction(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('reuploaded');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'correction_requested', 'comment' => 'Please provide a clearer scan.']);

        $this->assertDatabaseHas('vendor_documents', ['id' => $document->id, 'status' => 'correction_requested', 'review_comment' => 'Please provide a clearer scan.']);
    }

    // --- Need more info ---

    public function test_need_more_info_keeps_document_under_review(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('under_review');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'need_more_info', 'comment' => 'Is this the current year certificate?']);

        $this->assertDatabaseHas('vendor_documents', ['id' => $document->id, 'status' => 'under_review']);
        $this->assertDatabaseHas('reviews', ['decision' => 'need_more_info']);
    }

    // --- Permission gates ---

    public function test_vendor_user_cannot_make_review_decision(): void
    {
        $document   = $this->makeDocument('uploaded');
        $vendorUser = User::factory()->create(['role' => RoleName::VendorUser]);

        $this->actingAs($vendorUser)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved'])
            ->assertStatus(403);
    }

    public function test_auditor_cannot_make_review_decision(): void
    {
        $document = $this->makeDocument('uploaded');
        $auditor  = User::factory()->create(['role' => RoleName::Auditor]);

        $this->actingAs($auditor)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved'])
            ->assertStatus(403);
    }

    public function test_cannot_review_already_approved_document(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::Reviewer]);
        $document = $this->makeDocument('approved');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'rejected', 'comment' => 'Changing mind.'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('reviews', ['vendor_document_id' => $document->id]);
    }

    // --- Review history is append-only ---

    public function test_review_history_is_preserved_across_multiple_decisions(): void
    {
        $reviewer = User::factory()->create(['role' => RoleName::ComplianceAdmin]);
        $document = $this->makeDocument('uploaded');

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'correction_requested', 'comment' => 'Needs correction.']);

        $document->update(['status' => 'reuploaded']);

        $this->actingAs($reviewer)
            ->post(route('reviewer.documents.decide', $document), ['decision' => 'approved', 'comment' => '']);

        $this->assertSame(2, Review::where('vendor_document_id', $document->id)->count());
        $this->assertDatabaseHas('reviews', ['decision' => 'correction_requested']);
        $this->assertDatabaseHas('reviews', ['decision' => 'approved']);
    }
}
