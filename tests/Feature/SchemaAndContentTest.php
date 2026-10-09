<?php

namespace Tests\Feature;

use App\Enums\MediaRole;
use App\Enums\PublicationStatus;
use App\Models\Enquiry;
use App\Models\Media;
use App\Models\Package;
use App\Models\Project;
use App\Models\ProjectMedia;
use App\Models\RecordNote;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\StructuredText;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SchemaAndContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_scope_excludes_drafts_and_future_publication(): void
    {
        $live = Service::factory()->published()->create();
        Service::factory()->create();
        Service::factory()->create(['status' => PublicationStatus::Published, 'published_at' => now()->addDay()]);

        $this->assertSame([$live->id], Service::published()->pluck('id')->all());
    }

    public function test_publication_fields_are_not_mass_assignable(): void
    {
        $service = new Service(['title' => 'X', 'status' => 'published', 'published_at' => now()]);

        $this->assertNull($service->getAttributes()['published_at'] ?? null);
        $this->assertNotSame('published', $service->getAttributes()['status'] ?? null);
    }

    public function test_project_relationships(): void
    {
        $project = Project::factory()->published()->create();
        $service = Service::factory()->published()->create();
        $project->services()->attach($service);
        $media = Media::factory()->create();
        $link = new ProjectMedia(['caption' => 'Cover', 'sort_order' => 0]);
        $link->forceFill(['project_id' => $project->id, 'media_id' => $media->id, 'role' => MediaRole::Cover])->save();

        $fresh = Project::with(['category', 'services', 'cover.media'])->find($project->id);
        $this->assertNotNull($fresh->category);
        $this->assertSame($service->id, $fresh->services->first()->id);
        $this->assertSame($media->id, $fresh->cover->media->id);
        $this->assertTrue($media->isReferenced());
    }

    public function test_referenced_category_cannot_be_deleted(): void
    {
        $project = Project::factory()->create();

        $this->expectException(QueryException::class);
        $project->category->delete();
    }

    public function test_quote_package_never_shows_an_amount(): void
    {
        $package = Package::factory()->make(['price_amount' => null]);

        $this->assertSame('Request a quote', $package->priceLabel());
    }

    public function test_audit_logger_redacts_secrets_and_personal_fields(): void
    {
        $log = app(AuditLogger::class)->record('test', 'enquiry', 1, [
            'status' => 'contacted', 'message' => 'my secret plan', 'password' => 'hunter2', 'price_amount' => '10.00',
        ]);

        $this->assertSame(['status' => 'contacted', 'message' => '[changed]', 'price_amount' => '10.00'], $log->changed_fields);
        $this->expectException(LogicException::class);
        $log->forceFill(['action' => 'tampered'])->save();
    }

    public function test_audit_logger_rejects_unknown_resource_types(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(AuditLogger::class)->record('x', 'arbitrary_table');
    }

    public function test_note_must_have_exactly_one_parent(): void
    {
        $note = new RecordNote;
        $note->forceFill(['author_id' => User::factory()->create()->id, 'body' => 'x']);

        $this->expectException(LogicException::class);
        $note->save();
    }

    public function test_note_with_single_parent_saves(): void
    {
        $note = new RecordNote;
        $note->forceFill(['author_id' => User::factory()->create()->id, 'enquiry_id' => Enquiry::factory()->create()->id, 'body' => 'x'])->save();

        $this->assertTrue($note->exists);
    }

    public function test_seeders_are_idempotent_draft_only_and_create_no_users(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(4, Service::count());
        $this->assertSame(4, Package::count());
        $this->assertSame(0, Project::count());
        $this->assertSame(0, Service::published()->count() + Package::published()->count() + TeamMember::published()->count());
        $this->assertTrue(Package::all()->every(fn ($p) => $p->price_amount === null));
        $this->assertTrue(TeamMember::all()->every(fn ($m) => $m->is_placeholder));
    }

    public function test_structured_text_escapes_html(): void
    {
        $html = (string) StructuredText::toHtml("## Title <b>\n\n<script>alert(1)</script>\n\n- one\n- <img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('<h2>Title &lt;b&gt;</h2>', $html);
        $this->assertStringContainsString('<ul><li>one</li>', $html);
    }
}
